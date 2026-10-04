import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AhPageHeaderComponent } from '../../layout';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, AhPageHeaderComponent],
  selector: 'ah-placeholder-page',
  template: `
    <ah-page-header [title]="title" subtitle="The route is wired and ready for its scheduled domain milestone." />

    <section class="ah-section rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <p class="text-sm text-slate-700">{{ description }}</p>
      <a class="ah-link mt-4 inline-block font-semibold" routerLink="/overview">Return to overview</a>
    </section>
  `,
})
export class PlaceholderPage {
  private readonly route = inject(ActivatedRoute);

  protected readonly title = String(this.route.snapshot.data['title'] ?? 'Workspace');
  protected readonly description = String(this.route.snapshot.data['description'] ?? 'This workspace is not implemented yet.');
}
