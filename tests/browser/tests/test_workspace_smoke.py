from __future__ import annotations

import os
import re

from playwright.sync_api import Page, expect


def test_demo_owner_can_sign_in_open_patient_directory_and_sign_out(page: Page) -> None:
    base_url = os.environ.get("AUREVIA_WEB_BASE_URL", "http://aurevia.test:4200").rstrip("/")
    owner_email = os.environ.get("AUREVIA_E2E_OWNER_EMAIL", "owner@aurevia.local")
    owner_password = os.environ.get("AUREVIA_E2E_OWNER_PASSWORD", "AureviaLocal123!")

    page.goto(f"{base_url}/overview", wait_until="domcontentloaded")

    expect(page.get_by_role("heading", name="Sign in")).to_be_visible()
    page.get_by_label("Email").fill(owner_email)
    page.get_by_label("Password").fill(owner_password)
    page.get_by_role("button", name="Sign in").click()

    expect(page.get_by_role("button", name="Sign out")).to_be_visible()

    primary_navigation = page.get_by_role("complementary", name="Primary navigation")
    expect(primary_navigation.get_by_text("Clinical workspace", exact=True)).to_be_visible()

    primary_navigation.get_by_role("link", name="Patients").click()
    expect(page).to_have_url(re.compile(r"/patients$"))

    main = page.get_by_role("main")
    expect(main.get_by_text("Asha Mehta", exact=True)).to_be_visible()
    expect(main.get_by_text("Rahil Khan", exact=True)).to_be_visible()

    page.get_by_role("button", name="Sign out").click()
    expect(page.get_by_role("heading", name="Sign in")).to_be_visible()
