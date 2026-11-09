<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Book',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Belajar Laravel'),
        new OA\Property(property: 'author', type: 'string', example: 'Edwin'),
        new OA\Property(property: 'publisher', type: 'string', nullable: true, example: 'Penerbit Kampus'),
        new OA\Property(property: 'isbn', type: 'string', example: '9781234567890'),
        new OA\Property(property: 'published_year', type: 'integer', example: 2025),
        new OA\Property(property: 'stock', type: 'integer', example: 10),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Buku pengantar Laravel.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class BookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'isbn' => $this->isbn,
            'published_year' => $this->published_year,
            'stock' => $this->stock,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
