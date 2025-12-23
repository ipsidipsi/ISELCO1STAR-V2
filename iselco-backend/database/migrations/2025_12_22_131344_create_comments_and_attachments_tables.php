<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Comments table
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->json('read_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Attachments table (polymorphic)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('comments');
    }
};
