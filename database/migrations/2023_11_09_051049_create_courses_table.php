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
        Schema::create( 'courses', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'course_title' );
            $table->string( 'course_slug' );
            $table->string( 'level' );
            $table->string( 'duration' );
            $table->longText( 'summary' )->nullable();
            $table->string( 'feature_video' )->nullable();
            $table->timestamp( 'last_update' )->useCurrent();
            $table->double( 'course_price' );
            $table->foreignId( 'category_id' )->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string( 'course_feature_image' );
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
        Schema::dropIfExists( 'courses' );
    }
};
