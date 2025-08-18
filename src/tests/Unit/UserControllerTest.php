<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Controllers\UserController;
use App\Models\User;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // allow mass assignment in tests
        Model::unguard();

        // Gate: admin only
        Gate::define('manage-users', function (?User $user) {
            return $user && $user->role === 'admin';
        });
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- helpers ----------------

    private function makeUser(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'User ' . uniqid(),
            'email'      => 'user_' . uniqid('', true) . '@example.com',
            'password'   => Hash::make('secret'),
            'role'       => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('users')->insertGetId(array_replace($defaults, $overrides));
    }

    private function actingUser(string $role = 'user'): User
    {
        $id = $this->makeUser(['role' => $role]);
        $u  = User::findOrFail($id);
        $this->actingAs($u);
        return $u;
    }

    private function reqWithUser(string $method, string $uri, array $data = [], ?User $user = null): \Illuminate\Http\Request
    {
        $req = \Illuminate\Http\Request::create($uri, $method, $data);
        if ($user) {
            $req->setUserResolver(fn () => $user);
        }
        $req->headers->set('Accept', 'application/json');
        return $req;
    }


    // ---------------- index ----------------

    #[Test]
    public function it_index_returns_all_users_for_admin(): void
    {
        $this->actingUser('admin');
        $this->makeUser();
        $this->makeUser();

        $controller = app(UserController::class);
        $res = $controller->index();

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(3, $data); // admin + 2 created
    }

    #[Test]
    public function it_index_requires_admin(): void
    {
        $this->actingUser('user');

        $controller = app(UserController::class);
        $this->expectException(AuthorizationException::class);
        $controller->index();
    }

    // ---------------- show ----------------

    #[Test]
    public function it_show_allows_admin_to_view_any_user(): void
    {
        $admin = $this->actingUser('admin');
        $other = User::findOrFail($this->makeUser(['name' => 'Alice']));
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('GET', "/api/users/{$other->id}", [], $admin);
        $res = $controller->show($req, $other);

        $data = $res->getData(true);

        $this->assertSame($other->id, $data['id']);
        $this->assertSame('Alice', $data['name']);
    }

    #[Test]
    public function it_show_allows_user_to_view_self(): void
    {
        $self = $this->actingUser('user');
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('GET', "/api/users/{$self->id}", [], $self);
        $res = $controller->show($req, $self);
        $data = $res->getData(true);

        $this->assertSame($self->id, $data['id']);
    }

    #[Test]
    public function it_show_forbids_user_viewing_another_user(): void
    {
        $actor = $this->actingUser('user');
        $other = User::findOrFail($this->makeUser());
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('GET', "/api/users/{$other->id}", [], $actor);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->show($req, $other);
    }

    // ---------------- store ----------------

    #[Test]
    public function it_store_creates_user_when_admin(): void
    {
        $this->actingUser('admin');

        $controller = app(UserController::class);
        $req = Request::create('/api/users', 'POST', [
            'name'     => 'New User',
            'email'    => 'newuser@example.com',
            'password' => 'supersecret',
            'role'     => 'user',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('New User', $data['name']);
        $this->assertSame('user', $data['role']);

        // fetch DB record and assert password hashed
        $created = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('supersecret', $created->password));

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    #[Test]
    public function it_store_requires_admin(): void
    {
        $this->actingUser('user');

        $controller = app(UserController::class);
        $req = Request::create('/api/users', 'POST', [
            'name'     => 'X',
            'email'    => 'x@example.com',
            'password' => 'secret123',
            'role'     => 'user',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(AuthorizationException::class);
        $controller->store($req);
    }

    #[Test]
    public function it_store_fails_on_duplicate_email(): void
    {
        $this->actingUser('admin');
        $email = 'dup@example.com';
        $this->makeUser(['email' => $email]);

        $controller = app(UserController::class);
        $req = Request::create('/api/users', 'POST', [
            'name'     => 'Dup',
            'email'    => $email,
            'password' => 'secret123',
            'role'     => 'user',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    // ---------------- update ----------------

    #[Test]
    public function it_update_allows_admin_to_update_any_user_including_role_and_password(): void
    {
        $admin = $this->actingUser('admin');
        $target = User::findOrFail($this->makeUser([ 'name'=>'Old','email'=>'old@example.com','role'=>'user' ]));
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('PATCH', "/api/users/{$target->id}", [
            'name'=>'New','email'=>'new@example.com','role'=>'admin','password'=>'changed123'
        ], $admin);
        $res = $controller->update($req, $target);
        $data = $res->getData(true);

        $this->assertSame('New', $data['name']);
        $this->assertSame('new@example.com', $data['email']);
        $this->assertSame('admin', $data['role']);

        $fresh = User::findOrFail($target->id);
        $this->assertTrue(Hash::check('changed123', $fresh->password));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'admin']);
    }

    #[Test]
    public function it_update_allows_user_to_update_self_but_ignores_role_change(): void
    {
        $self = $this->actingUser('user');
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('PATCH', "/api/users/{$self->id}", [
            'name' => 'My New Name',
            'role' => 'admin',
        ], $self);
        $res = $controller->update($req, $self);
        $data = $res->getData(true);

        $this->assertSame('My New Name', $data['name']);
        $this->assertSame('user', $data['role']); // unchanged

        $this->assertDatabaseHas('users', ['id' => $self->id, 'role' => 'user', 'name' => 'My New Name']);
    }

    #[Test]
    public function it_update_forbids_user_editing_another_user(): void
    {
        $actor = $this->actingUser('user');
        $target = User::findOrFail($this->makeUser(['name'=>'Target']));
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('PATCH', "/api/users/{$target->id}", ['name'=>'Hack'], $actor);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->update($req, $target);
    }

    #[Test]
    public function it_update_fails_on_duplicate_email(): void
    {
        $admin = $this->actingUser('admin');
        $target = User::findOrFail($this->makeUser(['email'=>'target@example.com']));
        $other  = $this->makeUser(['email'=>'other@example.com']);
        $controller = app(\App\Http\Controllers\UserController::class);
        $req = $this->reqWithUser('PATCH', "/api/users/{$target->id}", ['email'=>'other@example.com'], $admin);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($req, $target);
    }

    // ---------------- destroy ----------------

    #[Test]
    public function it_destroy_deletes_user_when_admin(): void
    {
        $this->actingUser('admin');
        $victim = User::findOrFail($this->makeUser());

        $controller = app(UserController::class);
        $res  = $controller->destroy($victim);
        $data = $res->getData(true);

        $this->assertTrue($data['deleted']);
        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    }

    #[Test]
    public function it_destroy_requires_admin(): void
    {
        $this->actingUser('user');
        $victim = User::findOrFail($this->makeUser());

        $controller = app(UserController::class);

        $this->expectException(AuthorizationException::class);
        $controller->destroy($victim);
    }
}
