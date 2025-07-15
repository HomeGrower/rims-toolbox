<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DatastoreStructureVersion extends Model
{
    protected $fillable = [
        'version',
        'filename', 
        'structure',
        'file_size',
        'uploaded_by',
        'user_id',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public static function generateVersion()
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return sprintf('%s_%03d', $date, $count);
    }
    
    public static function createFromUpload($filename, $content)
    {
        // Deactivate current active version
        static::where('is_active', true)->update(['is_active' => false]);
        
        return static::create([
            'version' => static::generateVersion(),
            'filename' => $filename,
            'structure' => $content,
            'file_size' => strlen($content),
            'uploaded_by' => Auth::user()->name ?? 'System',
            'user_id' => Auth::id() ?? 1,
            'is_active' => true
        ]);
    }
    
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }
}
