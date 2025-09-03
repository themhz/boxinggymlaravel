<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\CommonMarkConverter;
use Mews\Purifier\Facades\Purifier; // if sanitizing HTML

class ArticleController extends Controller
{
    // GET /api/articles  (public: typically only published)
    // Optional filters: ?category=slug or ?category_id=1, ?status=published|draft
    public function index(Request $request): JsonResponse
    {
        $query = Article::query()->with(['category','author'])->latest('published_at')->latest('id');

        // public default: show only published unless explicit status filter present
        if (!$request->has('status')) {
            $query->where('status', 'published');
        } else {
            $query->where('status', $request->get('status'));
        }

        if ($slug = $request->get('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $slug));
        }

        if ($catId = $request->get('category_id')) {
            $query->where('category_id', $catId);
        }

        $items = $query->get();

        return response()->json([
            'result' => 'success',
            'data'   => $items,
        ]);
    }

    // GET /api/articles/{article}
    public function show(Article $article): JsonResponse
    {
        $env = new Environment([
            'html_input' => 'strip',          // ignore raw HTML inside Markdown
            'allow_unsafe_links' => false,
        ]);
        $converter = new CommonMarkConverter([], $env);

        $html = $converter->convert($article->content)->getContent();
        // If you want an extra safety net (esp. if you ever allow raw HTML):
        $html = Purifier::clean($html, 'default');

        return response()->json([
            'result' => 'success',
            'data'   => [
                'article'      => $article,
                'content_html' => $html, // deliver both raw MD and rendered HTML
            ],
        ]);
    }

    // POST /api/articles   (admin)
    public function store(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'slug'               => 'nullable|string|max:220|unique:articles,slug',
            'category_id'        => 'required|exists:categories,id',
            'excerpt'            => 'nullable|string|max:300',
            'content'            => 'required|string', // Markdown
            'status'             => 'nullable|in:draft,published,archived',
            'published_at'       => 'nullable|date',
            'featured_image_url' => 'nullable|url|max:255',
        ]);

        // Normalize/resolve slug
        $slug = $data['slug'] ? Str::slug($data['slug']) : Str::slug($data['title']);
        if (Article::where('slug', $slug)->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Slug already exists.',
            ], 422);
        }

        // Render Markdown -> HTML (strip raw HTML in Markdown for safety)
        $env = new Environment([
            'html_input'          => 'strip',  // ignore raw HTML inside MD
            'allow_unsafe_links'  => false,
        ]);
        $converter = new CommonMarkConverter([], $env);
        $renderedHtml = $converter->convert($data['content'])->getContent();

        // Extra safety net (recommended): sanitize the rendered HTML
        if (class_exists(Purifier::class)) {
            $renderedHtml = Purifier::clean($renderedHtml, 'default');
        }

        // If publishing now and no published_at provided, set it
        $status       = $data['status'] ?? 'draft';
        $published_at = $data['published_at'] ?? ($status === 'published' ? now() : null);

        $article = Article::create([
            'category_id'        => $data['category_id'],
            'author_id'          => $request->user()?->id, // nullable
            'title'              => $data['title'],
            'slug'               => $slug,
            'excerpt'            => $data['excerpt'] ?? null,
            'content'            => $data['content'],       // store Markdown
            'status'             => $status,
            'published_at'       => $published_at,
            'featured_image_url' => $data['featured_image_url'] ?? null,
        ]);

        // Return both Markdown and rendered HTML without changing your DB schema
        $payload = array_merge(
            $article->load(['category','author'])->toArray(),
            ['content_html' => $renderedHtml]
        );

        return response()->json([
            'result'  => 'success',
            'message' => 'Article created',
            'data'    => $payload,
        ], 201);
    }

    // PUT/PATCH /api/articles/{article}   (admin)
    public function update(Request $request, Article $article): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'title'              => 'sometimes|required|string|max:200',
            'slug'               => 'sometimes|nullable|string|max:220|unique:articles,slug,'.$article->id,
            'category_id'        => 'sometimes|required|exists:categories,id',
            'excerpt'            => 'sometimes|nullable|string|max:300',
            'content'            => 'sometimes|required|string', // Markdown
            'status'             => 'sometimes|in:draft,published,archived',
            'published_at'       => 'sometimes|nullable|date',
            'featured_image_url' => 'sometimes|nullable|url|max:255',
        ]);

        // Normalize slug if provided, and ensure uniqueness AFTER slugify
        if (array_key_exists('slug', $data)) {
            $normalized = $data['slug'] !== null ? Str::slug($data['slug']) : null;

            if ($normalized !== null) {
                $exists = \App\Models\Article::where('slug', $normalized)
                    ->where('id', '!=', $article->id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'result'  => 'error',
                        'message' => 'Slug already exists.',
                    ], 422);
                }
            }

            $data['slug'] = $normalized; // may be null to keep existing if you sent null intentionally
        }

        // If status provided and no explicit published_at, set/clear it smartly
        if (array_key_exists('status', $data) && ! array_key_exists('published_at', $data)) {
            if ($data['status'] === 'published') {
                $data['published_at'] = now();
            } elseif (in_array($data['status'], ['draft', 'archived'], true)) {
                $data['published_at'] = null;
            }
        }

        $article->fill($data)->save();

        // Render Markdown -> HTML for response (safe settings)
        $env = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $converter = new CommonMarkConverter([], $env);
        $html = $converter->convert($article->content ?? '')->getContent();

        // Optional: sanitize rendered HTML if purifier is installed
        if (class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            $html = Purifier::clean($html, 'default');
        }

        $fresh = $article->fresh()->load(['category','author']);

        return response()->json([
            'result'  => 'success',
            'message' => 'Article updated',
            'data'    => array_merge($fresh->toArray(), ['content_html' => $html]),
        ]);
    }
    // DELETE /api/articles/{article}   (admin)
    public function destroy(Request $request, Article $article): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $article->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Article deleted',
        ]);
    }
}
