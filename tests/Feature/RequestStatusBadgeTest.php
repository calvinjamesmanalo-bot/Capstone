<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequestStatusBadgeTest extends TestCase
{
    #[DataProvider('statuses')]
    public function test_request_status_labels_and_colors_render(string $status, string $label, string $color): void
    {
        $this->blade('<x-request-status-badge :status="$status" />', compact('status'))
            ->assertSee('>'.$label.'</span>', false)
            ->assertSee('bg-'.$color.'-50', false)
            ->assertSee('text-'.$color.'-800', false)
            ->assertSee('border-'.$color.'-200', false);
    }

    public static function statuses(): array
    {
        return [
            'pending' => ['pending', 'Pending', 'amber'],
            'processing' => ['processing', 'Processing', 'blue'],
            'processed' => ['processed', 'Processed', 'purple'],
            'ready' => ['ready_to_release', 'Ready for Release', 'indigo'],
            'completed' => ['completed', 'Released', 'emerald'],
            'rejected' => ['rejected', 'Rejected', 'red'],
        ];
    }

    public function test_unknown_status_has_a_neutral_escaped_label(): void
    {
        $status = '<script>alert("status")</script>';
        $this->blade('<x-request-status-badge :status="$status" />', compact('status'))
            ->assertSee($status)
            ->assertDontSee($status, false)
            ->assertSee('bg-slate-100', false)
            ->assertSee('text-slate-800', false);

        $this->blade('<x-request-status-badge status="awaiting_review" />')
            ->assertSee('Awaiting review')->assertSee('bg-slate-100', false);
        $this->blade('<x-request-status-badge />')->assertSee('Unknown');
    }

    public function test_component_preserves_and_escapes_additional_attributes(): void
    {
        $title = '"><script>alert(1)</script>';
        $this->blade('<x-request-status-badge status="pending" class="shadow-sm" :title="$title" />', compact('title'))
            ->assertSee('shadow-sm', false)
            ->assertSee($title)
            ->assertDontSee($title, false);
    }
}
