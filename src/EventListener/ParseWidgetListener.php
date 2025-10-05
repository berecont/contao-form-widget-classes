<?php

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

#[AsHook('parseWidget')]
class ParseWidgetListener
{
    public function __invoke(string $buffer, Widget $widget): string
    {
        $id = (string) $widget->id;

        // Text-Inputs (inkl. password/email/etc.)
        // if (in_array($widget->type, ['text', 'password', 'email', 'url', 'tel', 'search', 'number', 'date', 'time', 'datetime-local'], true)) {
        if ($widget->type === 'text') {
            $buffer = $this->addClassToOpenTagWithAttr($buffer, 'input',  'id',  'ctrl_'.$id, 'form-control');
            $buffer = $this->addClassToOpenTagWithAttr($buffer, 'label',  'for', 'ctrl_'.$id, 'myLabelClass');
            return $buffer;
        }

        // Textarea
        if ($widget->type === 'textarea') {
            $buffer = $this->addClassToOpenTagWithAttr($buffer, 'textarea', 'id',  'ctrl_'.$id, 'form-control');
            $buffer = $this->addClassToOpenTagWithAttr($buffer, 'label',    'for', 'ctrl_'.$id, 'myLabelClass');
            return $buffer;
        }

        // Submit
        if ($widget->type === 'submit') {
            $buffer = $this->addClassToOpenTagWithAttr($buffer, 'button', 'id',  'ctrl_'.$id, 'btn btn-dark');
            $buffer = $this->addClassToOpenTagWithAttr($buffer, 'input',  'id',  'ctrl_'.$id, 'myLabelClass');
            return $buffer;
        }        

        return $buffer;
    }

    /**
     * Fügt einer *spezifischen* öffnenden Tag-Instanz (per Attribut-Filter) eine Klasse hinzu,
     * ohne andere Elemente im Widget zu beeinflussen. Robust gegenüber vorhandenen/fehlenden class-Attributen.
     */
    private function addClassToOpenTagWithAttr(string $html, string $tag, string $attrName, string $attrValue, string $classToAdd): string
    {
        $pattern = sprintf(
            '#<%1$s\b([^>]*\s%2$s=(["\'])%3$s\2[^>]*)>#i',
            preg_quote($tag, '#'),
            preg_quote($attrName, '#'),
            preg_quote($attrValue, '#')
        );

        return preg_replace_callback($pattern, function ($m) use ($tag, $classToAdd) {
            $attrs = $m[1];

            // class vorhanden?
            if (preg_match('#\sclass=(["\'])([^"\']*)\1#i', $attrs, $cm)) {
                $quote   = $cm[1];
                $classes = preg_split('/\s+/', trim($cm[2])) ?: [];
                if (!in_array($classToAdd, $classes, true)) {
                    $classes[] = $classToAdd;
                }
                $newClass = ' class=' . $quote . trim(implode(' ', $classes)) . $quote;
                $attrs    = preg_replace('#\sclass=(["\'])([^"\']*)\1#i', $newClass, $attrs, 1);
            } else {
                // class fehlt → neu anhängen (vor dem schließenden > kommt bereits alles Relevante)
                $attrs .= ' class="' . htmlspecialchars($classToAdd, ENT_QUOTES) . '"';
            }

            return '<' . $tag . $attrs . '>';
        }, $html, 1) ?? $html;
    }
}
