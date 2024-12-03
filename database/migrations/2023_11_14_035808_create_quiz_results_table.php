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
        Schema::create( 'quiz_results', function ( Blueprint $table ) {
            $table->id();

            $table->foreignId( 'user_id' )
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId( 'course_id' )
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId( 'course_module_id' )
                ->constrained()->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId( 'quiz_id' )
                ->constrained()->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->float( 'total', 8, 2 );
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
        Schema::dropIfExists( 'quiz_results' );
    }
};
