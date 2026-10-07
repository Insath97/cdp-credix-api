<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    protected $fillable = [
        'customer_id',
        'signature_data',
        'signed_at'
    ];

    /**
     * Database-il ulla JSON string-ai auto-ah array-ah convert panna
     */
    protected $casts = [
        'signature_data' => 'array',
        'signed_at' => 'datetime',
    ];

    /**
     * Signature endha customer-ku uriyadhu endra relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Dynamically converts stored JSON vector coordinates into a printable SVG string.
     * DOMPDF or Browsershot can naturally parse this inside blade templates.
     */
    public function toSvg(): string
    {
        $width = $this->signature_data['width'] ?? 400;
        $height = $this->signature_data['height'] ?? 200;
        $lines = $this->signature_data['lines'] ?? [];

        $pathString = '';

        foreach ($lines as $line) {
            if (count($line) < 2) continue;

            // Move the vector pen to the initial point of the stroke
            $pathString .= "M {$line[0]['x']} {$line[0]['y']} ";

            // Draw line segments to all subsequent points in this stroke
            for ($i = 1; $i < count($line); $i++) {
                $pathString .= "L {$line[$i]['x']} {$line[$i]['y']} ";
            }
        }

        return "<svg viewBox='0 0 {$width} {$height}' width='100%' height='100%' xmlns='http://www.w3.org/2000/svg'>
                    <path d='{$pathString}' stroke='black' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/>
                </svg>";
    }

    
}
