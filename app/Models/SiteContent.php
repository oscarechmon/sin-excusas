<?php

namespace App\Models;

use App\Models\Concerns\HasCatalogImage;
use Illuminate\Database\Eloquent\Model;

/** Valor editado de un espacio de contenido de la web (ver config/site_contents.php). */
class SiteContent extends Model
{
    use HasCatalogImage;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'title', 'text', 'image_path'];
}
