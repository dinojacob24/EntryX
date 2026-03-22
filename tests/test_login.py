"""
EntryX Login Page — Selenium Test Suite
Covers: page load, UI elements, Google OAuth redirect, register link navigation.
Note: Full Google OAuth flow cannot be automated (Google blocks bot sign-ins).
      These tests validate everything the app controls on the login page.

Requirements:
    pip install selenium webdriver-manager pytest

Run:
    pytest tests/test_login.py -v
"""

import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# ── Config ────────────────────────────────────────────────────────────────────
BASE_URL = "http://localhost/Project/EntryX/pages/user_login.php"
REGISTER_URL = "http://localhost/Project/EntryX/pages/register.php"
TIMEOUT = 10  # seconds


# ── Fixtures ──────────────────────────────────────────────────────────────────
@pytest.fixture(scope="module")
def driver():
    """Visible Chrome driver — you can watch every test live."""
    options = Options()
    # headless OFF so you can see the browser
    options.add_argument("--start-maximized")

    service = Service(ChromeDriverManager().install())
    drv = webdriver.Chrome(service=service, options=options)
    drv.implicitly_wait(TIMEOUT)
    yield drv
    drv.quit()


@pytest.fixture(autouse=True)
def open_login_page(driver):
    """Navigate to login page before each test."""
    driver.get(BASE_URL)
    WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.TAG_NAME, "body"))
    )
    time.sleep(1)  # pause so you can see the page load


# ── Tests ─────────────────────────────────────────────────────────────────────

class TestLoginPageLoad:
    def test_page_title_contains_entryx(self, driver):
        """Page title should reference ENTRY X."""
        title = driver.title.lower()
        assert "entry" in title, f"Unexpected title: {driver.title}"

    def test_page_loads_without_php_errors(self, driver):
        """No raw PHP error text should appear on the page."""
        body = driver.find_element(By.TAG_NAME, "body").text
        for error_keyword in ["Parse error", "Fatal error", "Warning:", "Notice:"]:
            assert error_keyword not in body, \
                f"PHP error found on page: '{error_keyword}'"

    def test_login_heading_visible(self, driver):
        """'Login' heading should be visible."""
        heading = driver.find_element(By.XPATH, "//h2[contains(text(), 'Login')]")
        assert heading.is_displayed()

    def test_subtitle_text_visible(self, driver):
        """Subtitle about Google sign-in should be visible."""
        body = driver.find_element(By.TAG_NAME, "body").text
        assert "Google" in body, "Google sign-in instruction not found on page"


class TestGoogleSignInButton:
    def test_google_button_exists(self, driver):
        """Google Sign-in button/link should be present."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(@href, 'google_login') or contains(., 'Sign in with Google')]"
        )
        assert btn is not None

    def test_google_button_is_displayed(self, driver):
        """Google Sign-in button should be visible."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Sign in with Google')]"
        )
        assert btn.is_displayed()

    def test_google_button_href_points_to_auth(self, driver):
        """Google button href should point to api/auth.php with google_login action."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Sign in with Google')]"
        )
        href = btn.get_attribute("href")
        assert "auth.php" in href and "google_login" in href, \
            f"Unexpected href: {href}"

    def test_google_logo_image_loads(self, driver):
        """Google logo <img> inside the button should load (naturalWidth > 0)."""
        img = driver.find_element(
            By.XPATH, "//a[contains(., 'Sign in with Google')]//img"
        )
        natural_width = driver.execute_script("return arguments[0].naturalWidth;", img)
        assert natural_width > 0, "Google logo image failed to load"

    def test_google_button_click_redirects(self, driver):
        """Clicking Google button should redirect away from login page."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Sign in with Google')]"
        )
        btn.click()
        time.sleep(2)
        current_url = driver.current_url
        assert current_url != BASE_URL, \
            "Page did not redirect after clicking Google Sign-in"
        driver.get(BASE_URL)


class TestInfoSection:
    def test_internal_user_hint_visible(self, driver):
        """Hint about @ajce.in / @ac.in emails should be visible."""
        body = driver.find_element(By.TAG_NAME, "body").text
        assert "@ajce.in" in body or "@ac.in" in body, \
            "Internal user email hint not found"

    def test_external_user_hint_visible(self, driver):
        """Hint about external users registering first should be visible."""
        body = driver.find_element(By.TAG_NAME, "body").text
        assert "External" in body or "Register first" in body, \
            "External user hint not found"


class TestRegisterLink:
    def test_register_link_exists(self, driver):
        """'Register' link should be present on the login page."""
        link = driver.find_element(
            By.XPATH, "//a[contains(@href, 'register.php')]"
        )
        assert link.is_displayed()

    def test_register_link_navigates_correctly(self, driver):
        """Clicking Register should navigate to register.php."""
        link = driver.find_element(
            By.XPATH, "//a[contains(@href, 'register.php') and not(contains(@href, 'google'))]"
        )
        link.click()
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("register.php"))
        assert "register.php" in driver.current_url


class TestBackLink:
    def test_back_to_home_link_exists(self, driver):
        """'Back to Home' link should be present."""
        link = driver.find_element(
            By.XPATH, "//a[contains(text(), 'Back to Home') or contains(., 'Back')]"
        )
        assert link.is_displayed()

    def test_back_link_points_to_index(self, driver):
        """Back link should point to index.php or root."""
        link = driver.find_element(
            By.XPATH, "//a[contains(text(), 'Back to Home') or contains(., 'Back')]"
        )
        href = link.get_attribute("href")
        assert "index.php" in href or href.endswith("/EntryX/") or href.endswith("/"), \
            f"Unexpected back link href: {href}"


class TestMobileResponsiveness:
    def test_page_renders_on_mobile_viewport(self, driver):
        """Login page should render without horizontal scroll on 393px width."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        scroll_width = driver.execute_script("return document.body.scrollWidth")
        window_width = driver.execute_script("return window.innerWidth")
        assert scroll_width <= window_width + 5, \
            f"Horizontal overflow on mobile: scrollWidth={scroll_width}, windowWidth={window_width}"
        # Reset to desktop
        driver.set_window_size(1280, 800)

    def test_google_button_visible_on_mobile(self, driver):
        """Google Sign-in button should be visible on mobile viewport."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Sign in with Google')]"
        )
        assert btn.is_displayed()
        driver.set_window_size(1280, 800)
