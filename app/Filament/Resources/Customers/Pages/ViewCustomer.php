<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Actions\Filament\Customer\ValidateCustomerStatusChangeAction;
use App\Enums\Customer\CustomerStatusEnum;
use App\Exceptions\Customer\CustomerHasActiveTripException;
use App\Exceptions\Customer\CustomerHasPendingPaymentException;
use App\Filament\Resources\BaseViewRecord;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Infolists\CustomerInfolist;
use App\Models\Customer;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

class ViewCustomer extends BaseViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    protected function getCustomHeaderActions(): array
    {
        $validateAction = app(ValidateCustomerStatusChangeAction::class);

        return [
            Actions\Action::make('change_status')
                ->label(trans('customers.admin.fields.status'))
                ->icon('heroicon-o-signal')
                ->fillForm(fn (Customer $record): array => [
                    'status' => $record->{Customer::COLUMN_STATUS}->value,
                ])
                ->schema([
                    Select::make('status')
                        ->label(trans('customers.admin.fields.status'))
                        ->options([
                            CustomerStatusEnum::ACTIVE->value => CustomerStatusEnum::ACTIVE->getLabel(),
                            CustomerStatusEnum::INACTIVE->value => CustomerStatusEnum::INACTIVE->getLabel(),
                            CustomerStatusEnum::SUSPENDED->value => CustomerStatusEnum::SUSPENDED->getLabel(),
                        ])
                        ->native(false)
                        ->required(),
                ])
                ->action(function (Customer $record, array $data) use ($validateAction): void {
                    $newStatus = CustomerStatusEnum::from((int) $data['status']);
                    $currentStatus = $record->{Customer::COLUMN_STATUS};

                    try {
                        // Validate status change
                        $validateAction(
                            $record->{Customer::COLUMN_ID},
                            $currentStatus,
                            $newStatus
                        );
                    } catch (CustomerHasActiveTripException $e) {
                        Notification::make()
                            ->danger()
                            ->title(trans('customers.admin.exceptions.has_active_trip'))
                            ->send();

                        return;
                    } catch (CustomerHasPendingPaymentException $e) {
                        Notification::make()
                            ->danger()
                            ->title(trans('customers.admin.exceptions.has_pending_payment'))
                            ->send();

                        return;
                    }

                    $record->update([
                        Customer::COLUMN_STATUS => $newStatus,
                    ]);

                    $message = match ($newStatus) {
                        CustomerStatusEnum::ACTIVE => trans('customers.admin.notifications.activated'),
                        CustomerStatusEnum::INACTIVE => trans('customers.admin.notifications.deactivated'),
                        CustomerStatusEnum::SUSPENDED => trans('customers.admin.notifications.suspended'),
                    };

                    Notification::make()
                        ->success()
                        ->title($message)
                        ->send();
                }),

            Actions\EditAction::make(),
        ];
    }
}
