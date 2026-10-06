from __future__ import annotations

import os
import re
from collections.abc import Generator
from pathlib import Path

import pytest
from playwright.sync_api import Browser, BrowserContext, Page, Playwright, sync_playwright


def _artifact_name(node_id: str) -> str:
    return re.sub(r"[^A-Za-z0-9_.-]+", "-", node_id).strip("-")


@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item: pytest.Item, call: pytest.CallInfo[object]) -> Generator[None, object, None]:
    outcome = yield
    report = outcome.get_result()
    setattr(item, f"rep_{report.when}", report)


@pytest.fixture(scope="session")
def playwright_runtime() -> Generator[Playwright, None, None]:
    with sync_playwright() as runtime:
        yield runtime


@pytest.fixture(scope="session")
def browser(playwright_runtime: Playwright) -> Generator[Browser, None, None]:
    browser_instance = playwright_runtime.chromium.launch(headless=True)
    yield browser_instance
    browser_instance.close()


@pytest.fixture
def page(browser: Browser, request: pytest.FixtureRequest) -> Generator[Page, None, None]:
    context: BrowserContext = browser.new_context()
    context.tracing.start(screenshots=True, snapshots=True, sources=True)
    browser_page = context.new_page()

    yield browser_page

    report = getattr(request.node, "rep_call", None)
    failed = report is not None and report.failed
    if failed:
        artifact_dir = Path(os.environ.get("AUREVIA_E2E_ARTIFACTS_DIR", "/artifacts"))
        artifact_dir.mkdir(parents=True, exist_ok=True)
        artifact_name = _artifact_name(request.node.nodeid)
        browser_page.screenshot(path=artifact_dir / f"{artifact_name}.png", full_page=True)
        context.tracing.stop(path=artifact_dir / f"{artifact_name}.zip")
    else:
        context.tracing.stop()

    context.close()
