<?php

namespace Tests\Feature;

use App\Enums\ServiceOrderStatus;
use PHPUnit\Framework\TestCase;

class ServiceOrderStatusTest extends TestCase
{
    public function test_every_status_has_a_label_and_color(): void
    {
        foreach (ServiceOrderStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
            $this->assertNotEmpty($status->color());
        }
    }

    public function test_linear_workflow_transitions(): void
    {
        $this->assertSame(ServiceOrderStatus::Diagnosis, ServiceOrderStatus::Reception->nextStatus());
        $this->assertSame(ServiceOrderStatus::QuoteSent, ServiceOrderStatus::Diagnosis->nextStatus());
        $this->assertSame(ServiceOrderStatus::InProgress, ServiceOrderStatus::QuoteSent->nextStatus());
        $this->assertSame(ServiceOrderStatus::QualityControl, ServiceOrderStatus::InProgress->nextStatus());
        $this->assertSame(ServiceOrderStatus::Notified, ServiceOrderStatus::QualityControl->nextStatus());
        $this->assertSame(ServiceOrderStatus::ReadyPickup, ServiceOrderStatus::Notified->nextStatus());
        $this->assertSame(ServiceOrderStatus::Closed, ServiceOrderStatus::ReadyPickup->nextStatus());
    }

    public function test_terminal_statuses_have_no_next(): void
    {
        $this->assertNull(ServiceOrderStatus::Closed->nextStatus());
        $this->assertNull(ServiceOrderStatus::Cancelled->nextStatus());
    }

    public function test_each_status_maps_to_its_timestamp_column(): void
    {
        $this->assertSame('received_at', ServiceOrderStatus::Reception->timestampColumn());
        $this->assertSame('closed_at', ServiceOrderStatus::Closed->timestampColumn());
        $this->assertSame('cancelled_at', ServiceOrderStatus::Cancelled->timestampColumn());
    }
}
