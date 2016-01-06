<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceFontSupport extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{ 
		Schema::dropIfExists('fonts');  

		Schema::create('fonts', function($t)
		{
			$t->increments('id');
			
			$t->string('name');
			$t->string('folder');
			$t->string('css_stack');
			$t->smallInteger('css_weight')->default(400);
			$t->string('google_font');
			$t->string('normal');
			$t->string('bold');
			$t->string('italics');
			$t->string('bolditalics');
			$t->unsignedInteger('sort_order')->default(10000);
		}); 
		
		// Create fonts
		$seeder = new FontsSeeder();
		$seeder->run();

		Schema::table('accounts', function($table)
		{
			$table->unsignedInteger('header_font')->default(1);
			$table->unsignedInteger('body_font')->default(1);
		});

		Schema::table('accounts', function($table)
		{
      		$table->foreign('header_font')->references('id')->on('fonts');
			$table->foreign('body_font')->references('id')->on('fonts');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (Schema::hasColumn('accounts', 'header_font'))
		{
			Schema::table('accounts', function($table)
			{
				$table->dropForeign('accounts_header_font_foreign');
				$table->dropColumn('header_font');
			});
		}
		
		if (Schema::hasColumn('accounts', 'body_font'))
		{
			Schema::table('accounts', function($table)
			{
				$table->dropForeign('accounts_body_font_foreign');
				$table->dropColumn('body_font');
			});
		}

        Schema::dropIfExists('fonts');  
	}
	
}