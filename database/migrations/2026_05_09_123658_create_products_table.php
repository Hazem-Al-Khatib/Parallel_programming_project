<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id(); // هذا السطر ضروري لتعريف الرقم التعريفي التلقائي
        $table->string('name'); 
        $table->decimal('price', 10, 2); 
        $table->integer('stock'); 
        $table->timestamps(); // هذا ينشئ حقول وقت الإنشاء والتعديل تلقائياً
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
