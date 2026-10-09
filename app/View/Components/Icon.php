<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * Ikon SVG inline (Lucide, lisensi ISC) + ikon brand media sosial.
 * Pemakaian: <x-icon name="book-open" class="size-5" />
 */
class Icon extends Component
{
    /** @var array<string, string> */
    private static array $cache = [];

    public function __construct(public string $name, public ?float $stroke = null) {}

    /**
     * Render markup SVG dengan atribut tambahan dari pemanggil.
     */
    public function render(): string
    {
        $svg = self::$cache[$this->name] ??= $this->load();

        return str_replace('<svg', '<svg {{ $attributes->merge(["class" => "shrink-0", "aria-hidden" => "true"]) }}'
            .($this->stroke ? ' stroke-width="'.$this->stroke.'"' : ''), $svg);
    }

    /**
     * Muat & rapikan berkas SVG dari resources/svg/{brand,lucide}.
     */
    private function load(): string
    {
        foreach (['brand', 'lucide'] as $set) {
            $file = resource_path("svg/{$set}/{$this->name}.svg");
            if (is_file($file)) {
                $svg = (string) file_get_contents($file);
                $svg = (string) preg_replace(['/<!--.*?-->/s', '/\s*class="[^"]*"/', '/\s*width="24"/', '/\s*height="24"/', '/\s+/'], ['', '', '', '', ' '], $svg);

                return trim(str_replace(['> <', ' />'], ['><', '/>'], $svg));
            }
        }

        return '<svg viewBox="0 0 24 24" data-icon-missing="'.e($this->name).'"></svg>';
    }
}
