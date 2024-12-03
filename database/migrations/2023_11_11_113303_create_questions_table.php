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
        Schema::create( 'questions', function ( Blueprint $table ) {
            $table->id();

            $table->foreignId( 'quiz_id' )
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string( 'question' );
            $table->enum( 'type', [1, 2] )->default( 1 )->comment( '1 = MCQ, 2 = Fill in the Gap' );
            $table->string( 'option_one' )->nullable();
            $table->string( 'option_two' )->nullable();
            $table->string( 'option_three' )->nullable();
            $table->string( 'option_four' )->nullable();
            $table->string( 'answer' );
            $table->string( 'note' )->nullable();

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
        Schema::dropIfExists( 'questions' );
    }
};
