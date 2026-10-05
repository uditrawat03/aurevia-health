import { ChangeDetectionStrategy, Component, computed, input, output } from '@angular/core';

export interface AhTabItem {
  readonly id: string;
  readonly label: string;
  readonly badge?: string | number;
  readonly disabled?: boolean;
}

export type AhTabsVariant = 'line' | 'pills';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-tabs',
  template: `
    <nav [class]="classes()" [attr.aria-label]="ariaLabel()" role="tablist">
      @for (item of items(); track item.id) {
        <button
          class="ah-tab"
          [class.is-active]="item.id === activeId()"
          [attr.aria-selected]="item.id === activeId()"
          [attr.tabindex]="item.id === activeId() && !item.disabled ? 0 : -1"
          [disabled]="item.disabled"
          role="tab"
          type="button"
          (click)="select(item)"
          (keydown)="onKeydown($event, item)"
        >
          {{ item.label }}
          @if (item.badge !== undefined) {
            <span class="ah-badge ah-badge-info">{{ item.badge }}</span>
          }
        </button>
      }
    </nav>
  `,
})
export class AhTabsComponent {
  readonly items = input.required<readonly AhTabItem[]>();
  readonly activeId = input.required<string>();
  readonly ariaLabel = input<string>('Sections');
  readonly variant = input<AhTabsVariant>('line');
  readonly activeIdChange = output<string>();

  protected readonly classes = computed(() => (this.variant() === 'pills' ? 'ah-tabs ah-tabs-pills' : 'ah-tabs'));

  protected select(item: AhTabItem): void {
    if (!item.disabled && item.id !== this.activeId()) {
      this.activeIdChange.emit(item.id);
    }
  }

  protected onKeydown(event: KeyboardEvent, item: AhTabItem): void {
    const enabledItems = this.items().filter((candidate) => !candidate.disabled);
    const currentIndex = enabledItems.findIndex((candidate) => candidate.id === item.id);
    if (currentIndex < 0 || enabledItems.length === 0) {
      return;
    }

    let targetIndex: number;
    switch (event.key) {
      case 'ArrowRight':
        targetIndex = (currentIndex + 1) % enabledItems.length;
        break;
      case 'ArrowLeft':
        targetIndex = (currentIndex - 1 + enabledItems.length) % enabledItems.length;
        break;
      case 'Home':
        targetIndex = 0;
        break;
      case 'End':
        targetIndex = enabledItems.length - 1;
        break;
      default:
        return;
    }

    event.preventDefault();
    const target = enabledItems[targetIndex];
    if (!target) {
      return;
    }
    if (target.id !== this.activeId()) {
      this.activeIdChange.emit(target.id);
    }

    const tabs = (event.currentTarget as HTMLElement | null)?.parentElement?.querySelectorAll<HTMLButtonElement>(
      '[role="tab"]:not(:disabled)',
    );
    tabs?.[targetIndex]?.focus();
  }
}
