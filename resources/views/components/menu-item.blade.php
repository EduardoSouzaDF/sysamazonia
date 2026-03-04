@props(['items', 'level' => 0])

@forelse($items as $item)
    @if(isset($item['heading']))
        <div class="kt-menu-item pt-2.25 pb-px">
            <span class="kt-menu-heading uppercase text-xs font-medium text-muted-foreground ps-[10px] pe-[10px]">
                {{ $item['heading'] }}
            </span>
        </div>
    @elseif(isset($item['children']) && count($item['children']) > 0)
        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
            <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                @if(isset($item['icon']))
                    <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                        <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                    </span>
                @else
                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                @endif
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                    {{ $item['title'] }}
                </span>
                <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                    <span class="inline-flex kt-menu-item-show:hidden">
                        <i class="ki-filled ki-plus text-[11px]"></i>
                    </span>
                    <span class="hidden kt-menu-item-show:inline-flex">
                        <i class="ki-filled ki-minus text-[11px]"></i>
                    </span>
                </span>
            </div>
            <div class="kt-menu-accordion gap-1 @if($level > 0) relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-border @else ps-[10px] @endif">
                <x-menu-item :items="$item['children']" :level="$level + 1" />
            </div>
        </div>
    @else
        <div class="kt-menu-item">
            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg @if($level > 0) gap-[5px] ps-[10px] pe-[10px] py-[8px] @else gap-[10px] ps-[10px] pe-[10px] py-[6px] @endif" href="{{ isset($item['route']) ? route($item['route']) : '#' }}" tabindex="0">
                @if($level > 0)
                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                        {{ $item['title'] }}
                    </span>
                @else
                    @if(isset($item['icon']))
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="ki-filled {{ $item['icon'] }} text-lg"></i>
                        </span>
                    @endif
                    <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                        {{ $item['title'] }}
                    </span>
                @endif
            </a>
        </div>
    @endif
@empty
@endforelse
