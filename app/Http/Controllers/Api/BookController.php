<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: '/api/books',
        operationId: 'booksIndex',
        summary: 'Daftar buku dengan pencarian dan paginasi',
        security: [['sanctum' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Cari berdasarkan judul atau nama penulis',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Jumlah buku per halaman (1-100, default 10)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Nomor halaman',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar buku',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Book')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $search = $request->string('search')->trim()->toString();

        $books = Book::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }

    #[OA\Post(
        path: '/api/books',
        operationId: 'booksStore',
        summary: 'Tambah buku',
        security: [['sanctum' => []]],
        tags: ['Books'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Buku berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Data buku tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/books/{book}',
        operationId: 'booksShow',
        summary: 'Detail buku',
        security: [['sanctum' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(
                name: 'book',
                in: 'path',
                description: 'ID buku',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail buku',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Buku tidak ditemukan',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
        ]
    )]
    public function show(Book $book): BookResource
    {
        return new BookResource($book);
    }

    #[OA\Put(
        path: '/api/books/{book}',
        operationId: 'booksUpdate',
        summary: 'Ubah data buku',
        security: [['sanctum' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(
                name: 'book',
                in: 'path',
                description: 'ID buku',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Buku berhasil diubah',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Buku tidak ditemukan',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Data buku tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    #[OA\Patch(
        path: '/api/books/{book}',
        operationId: 'booksUpdatePartial',
        summary: 'Ubah sebagian data buku',
        security: [['sanctum' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(
                name: 'book',
                in: 'path',
                description: 'ID buku',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Buku berhasil diubah',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Buku tidak ditemukan',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Data buku tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book->update($request->validated());

        return new BookResource($book);
    }

    #[OA\Delete(
        path: '/api/books/{book}',
        operationId: 'booksDestroy',
        summary: 'Hapus buku',
        security: [['sanctum' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(
                name: 'book',
                in: 'path',
                description: 'ID buku',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Buku berhasil dihapus',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Buku tidak ditemukan',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
        ]
    )]
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus']);
    }
}
