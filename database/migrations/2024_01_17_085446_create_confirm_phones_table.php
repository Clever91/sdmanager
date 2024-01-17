<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('confirm_phones', function (Blueprint $table) {
            $table->id();
            $table->string("phone");
            $table->string("app_type")->default(User::TYPE_MANAGER)->comment("application type");
            $table->integer("confirm_code")->nullable();
            $table->integer("expire_time")->nullable();
            $table->timestamps();
            $table->unique(["phone", "app_type"], 'idx_unique_phone_app_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('confirm_phones');
    }
};
