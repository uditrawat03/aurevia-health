import { ChangeDetectionStrategy, Component, input } from '@angular/core';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-form-field',
  styles: `
    :host {
      display: block;
    }
  `,
  template: `
    <label [attr.for]="forId()">
      <span class="ah-label">
        {{ label() }}
        @if (required()) {
          <span class="text-red-600" aria-hidden="true">*</span>
        }
      </span>
      <ng-content />
    </label>

    @if (error()) {
      <div class="mt-1 text-[0.6875rem] font-semibold text-red-700" role="alert">{{ error() }}</div>
    } @else if (hint()) {
      <div class="mt-1 text-[0.6875rem] text-slate-500">{{ hint() }}</div>
    }
  `,
})
export class AhFormFieldComponent {
  readonly label = input.required<string>();
  readonly forId = input.required<string>();
  readonly hint = input<string>('');
  readonly error = input<string>('');
  readonly required = input(false);
}
