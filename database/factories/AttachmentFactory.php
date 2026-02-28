<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'jpg', 'png', 'docx']);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return [
            'attachable_type' => Issue::class,
            'attachable_id' => Issue::factory(),
            'original_name' => fake()->word().'.'.$extension,
            'stored_name' => Str::uuid().'.'.$extension,
            'mime_type' => $mimeTypes[$extension],
            'size_bytes' => fake()->numberBetween(1024, 5242880),
            'uploaded_by' => User::factory(),
        ];
    }
}
