<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

class ArticlesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Pick an existing user as the author (no creation)
        $author    = User::query()->orderBy('id')->first();
        $authorId  = $author?->id;
        if (! $authorId) {
            $this->command?->warn('ArticlesTableSeeder: No users found; author_id will be null.');
        }

        // Ensure required categories exist
        $blog = Category::firstOrCreate(
            ['slug' => 'blog'],
            ['name' => 'Blog', 'description' => 'Blog posts']
        );

        $website = Category::firstOrCreate(
            ['slug' => 'website-content'],
            ['name' => 'Website Content', 'description' => 'Static site pages']
        );

        $now = Carbon::now();

        $articles = [
            // BLOG POSTS (Markdown content)
            [
                'category_id'        => $blog->id,
                'author_id'          => $authorId,
                'title'              => 'Welcome to Our Boxing Gym',
                'slug'               => 'welcome-to-our-boxing-gym',
                'excerpt'            => 'A quick tour of our space, coaches, and training philosophy.',
                'content'            => <<<MD
Welcome! We’re excited to have you. Our gym focuses on **smart**, **safe**, and **effective** training for all levels.

## What to Expect

- **Warm-up:** mobility, jump rope, and activation drills  
- **Technique:** stance, guard, footwork, and combos  
- **Conditioning:** bag work, pads, and circuits  

_Tip:_ bring water, a towel, and arrive **10 minutes early** for your first session.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(10),
                'featured_image_url' => null,
            ],
            [
                'category_id'        => $blog->id,
                'author_id'          => $authorId,
                'title'              => 'Beginner’s Guide: Your First Boxing Class',
                'slug'               => 'beginners-guide-first-boxing-class',
                'excerpt'            => 'Gear checklist and mindset tips to start strong.',
                'content'            => <<<MD
Starting out? Here’s how to make your first session smooth.

## Gear Checklist
- Hand wraps and gloves _(loaners available)_
- Comfortable shoes and breathable clothes
- Water bottle

## Mindset
Focus on learning technique over power. **Consistency beats intensity**.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(7),
                'featured_image_url' => null,
            ],
            [
                'category_id'        => $blog->id,
                'author_id'          => $authorId,
                'title'              => 'Trainer Spotlight: Coach Maria',
                'slug'               => 'trainer-spotlight-coach-maria',
                'excerpt'            => 'Meet Coach Maria—former national champion and technique specialist.',
                'content'            => <<<MD
Coach Maria brings **10+ years** of competitive experience and an eye for detail.

### Specialties
- Southpaw footwork
- Counter-punching
- Pad work for timing and rhythm

Catch her classes on **Tuesdays** and **Thursdays**.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(4),
                'featured_image_url' => null,
            ],

            // WEBSITE CONTENT (Markdown)
            [
                'category_id'        => $website->id,
                'author_id'          => $authorId,
                'title'              => 'About Us',
                'slug'               => 'about-us',
                'excerpt'            => 'Who we are and what drives our coaching approach.',
                'content'            => <<<MD
We’re a **community-first** boxing gym dedicated to helping beginners and athletes reach their goals.

## Our Philosophy
Technique, consistency, and fun. We create an environment where everyone can learn safely and progress.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(12),
                'featured_image_url' => null,
            ],
            [
                'category_id'        => $website->id,
                'author_id'          => $authorId,
                'title'              => 'Membership & Pricing',
                'slug'               => 'membership-and-pricing',
                'excerpt'            => 'Simple plans for every training schedule.',
                'content'            => <<<MD
## Plans

- **Starter:** 2 classes/week  
- **Standard:** 3 classes/week  
- **Unlimited:** all classes  

Ask our front desk for **student** and **family** discounts.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(11),
                'featured_image_url' => null,
            ],
            [
                'category_id'        => $website->id,
                'author_id'          => $authorId,
                'title'              => 'FAQ',
                'slug'               => 'faq',
                'excerpt'            => 'Answers to the most common questions.',
                'content'            => <<<MD
## Do I need experience?
Nope! We love beginners.

## Do you provide gloves?
Yes, loaners are available for first-timers.

## How do I book?
Use our **online booking** system to reserve your slot.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(9),
                'featured_image_url' => null,
            ],
            [
                'category_id'        => $website->id,
                'author_id'          => $authorId,
                'title'              => 'Contact',
                'slug'               => 'contact',
                'excerpt'            => 'Get in touch for questions and private sessions.',
                'content'            => <<<MD
Email us at **info@example.com** or call **+30 210 000 0000**.

Visit us at **Tavros, Attica**. Parking available nearby.
MD,
                'status'             => 'published',
                'published_at'       => $now->copy()->subDays(8),
                'featured_image_url' => null,
            ],
        ];

        foreach ($articles as $a) {
            Article::updateOrCreate(['slug' => $a['slug']], $a);
        }
    }
}
