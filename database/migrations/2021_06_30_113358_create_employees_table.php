<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('email')->nullable();
			$table->string('telephone')->nullable();
			$table->string('alt_telephone')->nullable();
			$table->string('designation')->nullable();
			$table->enum('gender',['M','F'])->default('M');
			$table->date('dob')->nullable();
			$table->date('join_date')->nullable();
			$table->text('address')->nullable();
			$table->string('image')->nullable();
			$table->string('password')->nullable();
			$table->enum('status',['A','D'])->default('A');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
