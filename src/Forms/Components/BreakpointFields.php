<?php

namespace Threls\FilamentPageBuilder\Forms\Components;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;

class BreakpointFields
{
    public const BREAKPOINTS = ['xs', 'sm', 'md', 'lg', 'xl'];

    public static function number(string $label, string $statePath, ?string $suffix = 'px'): Fieldset
    {
        $inputs = [];
        foreach (self::BREAKPOINTS as $bp) {
            $inputs[] = TextInput::make($bp)
                ->label($bp)
                ->numeric()
                ->minValue(0)
                ->suffix($suffix);
        }

        return Fieldset::make($label)
            ->schema($inputs)
            ->statePath($statePath);
    }

    public static function select(string $label, string $statePath, array $options): Fieldset
    {
        $inputs = [];
        foreach (self::BREAKPOINTS as $bp) {
            $inputs[] = Select::make($bp)
                ->label($bp)
                ->options($options)
                ->native(false)
                ->placeholder('Inherit');
        }

        return Fieldset::make($label)
            ->schema($inputs)
            ->statePath($statePath);
    }

     /**
      * Renders a single-value or per-breakpoint numeric input group under a single state path.
      * Stores internal meta keys __mode and __single which should be cleaned on save.
      */
    public static function numberFlexible(string $label, string $statePath, ?string $suffix = 'px'): Fieldset
    {
        $bpInputs = [];
        foreach (self::BREAKPOINTS as $bp) {
            $bpInputs[] = TextInput::make($bp)
                ->label($bp)
                ->numeric()
                ->minValue(0)
                ->suffix($suffix);
        }

        $single = TextInput::make('__single')
            ->label('Value')
            ->suffix($suffix)
            ->numeric()
            ->minValue(0);

        return self::buildFlexibleFieldset(
            label: $label,
            statePath: $statePath,
            singleComponent: $single,
            breakpointInputs: $bpInputs
        );
    }

    /**
     * Renders a single-value or per-breakpoint select input group under a single state path.
     * Stores internal meta keys __mode and __single which should be cleaned on save.
     */
    public static function selectFlexible(string $label, string $statePath, array $options): Fieldset
    {
        $bpInputs = [];
        foreach (self::BREAKPOINTS as $bp) {
            $bpInputs[] = Select::make($bp)
                ->label($bp)
                ->options($options)
                ->native(false)
                ->placeholder('Inherit');
        }

        $single = Select::make('__single')
            ->label('Value')
            ->options($options)
            ->native(false)
            ->placeholder('Select value');

        return self::buildFlexibleFieldset(
            label: $label,
            statePath: $statePath,
            singleComponent: $single,
            breakpointInputs: $bpInputs
        );
    }

    /**
     * Shared flexible builder: adds the mode toggle, a single-value input, and a breakpoint group.
     */
    private static function buildFlexibleFieldset(
        string $label,
        string $statePath,
        $singleComponent,
        array $breakpointInputs,
        int $modeSpanLg = 3,
        int $contentSpanLg = 9,
        int $bpColumns = 3,
    ): Fieldset {
        $toggle = self::makeModeToggle($modeSpanLg);

        // Apply shared visibility and spans
        $singleComponent = $singleComponent
            ->columnSpan(['lg' => $contentSpanLg])
            ->visible(fn (Get $get) => ($get('__mode') ?? 'single') === 'single');

        $bpGroup = Group::make($breakpointInputs)
            ->columns($bpColumns)
            ->columnSpan(['lg' => $contentSpanLg])
            ->visible(fn (Get $get) => ($get('__mode') ?? 'single') === 'breakpoints');

        return Fieldset::make($label)
            ->schema([
                $toggle,
                $singleComponent,
                $bpGroup,
            ])
            ->columns(12)
            ->statePath($statePath);
    }

    /**
     * Creates the reactive Mode toggle with shared behavior.
     */
    private static function makeModeToggle(int $modeSpanLg = 3): ToggleButtons
    {
        return ToggleButtons::make('__mode')
            ->label('Mode')
            ->options([
                'single' => 'Single',
                'breakpoints' => 'Breakpoints',
            ])
            ->inline()
            ->default('single')
            ->live()
            ->columnSpan(['lg' => $modeSpanLg])
            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                if ($state === 'breakpoints') {
                    $single = $get('__single');
                    $xs = $get('xs');
                    if (($xs === null || $xs === '') && ($single !== null && $single !== '')) {
                        $set('xs', $single);
                    }
                } elseif ($state === 'single') {
                    $single = $get('__single');
                    if ($single === null || $single === '') {
                        $candidate = $get('xs');
                        if ($candidate === null || $candidate === '') {
                            foreach (self::BREAKPOINTS as $bp) {
                                $v = $get($bp);
                                if ($v !== null && $v !== '') {
                                    $candidate = $v;
                                    break;
                                }
                            }
                        }
                        if ($candidate !== null && $candidate !== '') {
                            $set('__single', $candidate);
                        }
                    }
                }
            })
            ->afterStateHydrated(function (Set $set, Get $get, $state) {
                if ($state !== null && $state !== '') {
                    return; // Respect stored mode if present
                }
                $hasAny = false;
                $hasBeyondXs = false;
                foreach (self::BREAKPOINTS as $bp) {
                    $v = $get($bp);
                    if ($v !== null && $v !== '') {
                        $hasAny = true;
                        if ($bp !== 'xs') {
                            $hasBeyondXs = true;
                        }
                    }
                }
                if ($hasBeyondXs) {
                    $set('__mode', 'breakpoints');
                } elseif ($hasAny) {
                    $set('__mode', 'single');
                    $xs = $get('xs');
                    if ($xs !== null && $xs !== '') {
                        $set('__single', $xs);
                    }
                }
            });
    }
}
