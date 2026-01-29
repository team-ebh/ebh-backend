<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Actions\Filament\Customer\ValidateCustomerStatusChangeAction;
use App\Enums\Customer\CustomerStatusEnum;
use App\Exceptions\Customer\CustomerHasActiveTripException;
use App\Exceptions\Customer\CustomerHasPendingPaymentException;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Prevent editing deleted customers
        if ($this->record->isDeleted()) {
            Notification::make()
                ->warning()
                ->title(trans('customers.admin.exceptions.cannot_edit_deleted'))
                ->send();

            $this->redirect(CustomerResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $validateAction = app(ValidateCustomerStatusChangeAction::class);

        $currentStatus = $this->record->getOriginal(Customer::COLUMN_STATUS);
        $newStatus = $this->data['status'] ?? null;

        // Check if status is being changed
        if ($currentStatus !== $newStatus && $newStatus !== null) {
            // Handle enum objects
            $currentStatusEnum = $currentStatus instanceof CustomerStatusEnum
                ? $currentStatus
                : CustomerStatusEnum::from((int) $currentStatus);

            $newStatusEnum = $newStatus instanceof CustomerStatusEnum
                ? $newStatus
                : CustomerStatusEnum::from((int) $newStatus);

            try {
                $validateAction(
                    $this->record->{Customer::COLUMN_ID},
                    $currentStatusEnum,
                    $newStatusEnum
                );
            } catch (CustomerHasActiveTripException $e) {
                Notification::make()
                    ->danger()
                    ->title(trans('customers.admin.exceptions.has_active_trip'))
                    ->send();

                $this->halt();
            } catch (CustomerHasPendingPaymentException $e) {
                Notification::make()
                    ->danger()
                    ->title(trans('customers.admin.exceptions.has_pending_payment'))
                    ->send();

                $this->halt();
            }
        }
    }
}
