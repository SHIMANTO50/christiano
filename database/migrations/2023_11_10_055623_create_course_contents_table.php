<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'course_contents', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'content_title' );
            $table->string( 'video_url' );
            $table->string( 'content_length' );
            $table->foreignId( 'course_id' )->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId( 'course_module_id' )->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->enum( 'video_source', [1, 2, 3] )->default( 1 )->comment( '1 = Youtube, 2 = Vimeo, 3 = Custom URL' );
            $table->enum( 'status', [0, 1] )->default( 1 )->comment( '0 = De-Active, 1 = Active' );
            $table->timestamp( 'created_at' )->useCurrent();
            $table->timestamp( 'updated_at' )->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'course_contents' );
    }
};
