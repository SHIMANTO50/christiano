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
        Schema::create( 'bundle_favourites', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'user_id' )->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId( 'bundle_id' )->constrained()->restrictOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists( 'bundle_favourites' );
    }
};
