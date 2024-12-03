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
        Schema::create( 'quizzes', function ( Blueprint $table ) {
            $table->id();

            $table->string( 'title' );
            $table->string( 'slug' );

            // Forign Id
            $table->foreignId( 'course_id' )
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId( 'course_module_id' )
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string( 'time' )->nullable();
            $table->float( 'pass_mark' )->nullable();

            $table->enum( 'status', [0, 1] )->default( 1 )->comment( '0 = De-Active, 1 = Active' );
            $table->timestamps();
            $table->softDeletes();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'quizzes' );
    }
};
