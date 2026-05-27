<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop unused vendors tables — vendors are POS users with role VENDEDORES NIVEL 1
     * or VENDEDOR PLUS, no separate entity required.
     */
    public function up()
    {
        Schema::dropIfExists('vendor_locations');
        Schema::dropIfExists('vendors');
    }

    public function down()
    {
        // No-op — the original create migrations still exist; rolling back would
        // require re-running those.
    }
};
