"""
EntryX Landing Page (index.php) — Selenium Test Suite
Covers: page load, hero section, CTA buttons, feature cards,
        navigation links, footer, mobile responsiveness.

Run:
    python -m pytest tests/test_index.py -v
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
BASE_URL  = "http://localhost/Project/EntryX/"
LOGIN_URL = "http://localhost/Project/EntryX/pages/user_login.php"
ADMIN_URL = "http://localhost/Project/EntryX/pages/admin_login.php"
SECURITY_URL = "http://localhost/Project/EntryX/pages/sub_admin_login.php"
TIMEOUT   = 10


# ── Fixtures ──────────────────────────────────────────────────────────────────
@pytest.fixture(scope="module")
def driver():
    """Visible Chrome driver shared across all tests."""
    options = Options()
    options.add_argument("--start-maximized")
    service = Service(ChromeDriverManager().install())
    drv = webdriver.Chrome(service=service, options=options)
    drv.implicitly_wait(TIMEOUT)
    yield drv
    drv.quit()


@pytest.fixture(autouse=True)
def open_index(driver):
    """Navigate to landing page before each test."""
    driver.get(BASE_URL)
    WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.TAG_NAME, "body"))
    )
    time.sleep(0.8)


# ── Tests ─────────────────────────────────────────────────────────────────────

class TestPageLoad:
    def test_page_loads_without_php_errors(self, driver):
        """No raw PHP error text should appear."""
        body = driver.find_element(By.TAG_NAME, "body").text
        for kw in ["Parse error", "Fatal error", "Warning:", "Notice:"]:
            assert kw not in body, f"PHP error on page: '{kw}'"

    def test_page_title_contains_entry(self, driver):
        """Page title should reference ENTRY X."""
        assert "entry" in driver.title.lower(), f"Unexpected title: {driver.title}"

    def test_page_does_not_redirect_when_logged_out(self, driver):
        """Unauthenticated visit should stay on index, not redirect."""
        assert "index.php" in driver.current_url or driver.current_url.rstrip("/").endswith("EntryX"), \
            f"Unexpected redirect: {driver.current_url}"

    def test_nav_bar_present(self, driver):
        """Global nav bar should be visible."""
        nav = driver.find_element(By.TAG_NAME, "nav")
        assert nav.is_displayed()

    def test_footer_present(self, driver):
        """Footer should be present on the page."""
        footer = driver.find_element(By.TAG_NAME, "footer")
        assert footer is not None


class TestHeroSection:
    def test_hero_heading_visible(self, driver):
        """Main hero heading should be visible."""
        heading = driver.find_element(By.TAG_NAME, "h1")
        assert heading.is_displayed()
        assert len(heading.text.strip()) > 0

    def test_hero_heading_contains_event(self, driver):
        """Hero heading should mention 'Event' or 'Engineering'."""
        heading = driver.find_element(By.TAG_NAME, "h1")
        text = heading.text.lower()
        assert "event" in text or "engineering" in text or "elite" in text, \
            f"Unexpected hero heading: {heading.text}"

    def test_hero_subtitle_visible(self, driver):
        """Hero subtitle paragraph should be visible."""
        subtitle = driver.find_element(
            By.XPATH, "//p[contains(., 'premier platform') or contains(., 'Smart registrations')]"
        )
        assert subtitle.is_displayed()

    def test_live_badge_visible(self, driver):
        """'Next-Gen Campus Ecosystem' badge should be visible."""
        badge = driver.find_element(
            By.XPATH, "//span[contains(., 'Campus Ecosystem') or contains(., 'Next-Gen')]"
        )
        assert badge.is_displayed()

    def test_pulsing_dot_present(self, driver):
        """Animated red dot in the badge should be in the DOM."""
        # It's a div with brand background and border-radius 50%
        dots = driver.find_elements(
            By.XPATH, "//div[contains(@style, 'border-radius: 50%') and contains(@style, 'var(--p-brand)')]"
        )
        assert len(dots) > 0, "Pulsing dot not found in hero badge"


class TestCTAButtons:
    def test_access_portal_button_exists(self, driver):
        """'Access Portal' / login button should be visible."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Access Portal') or contains(@href, 'user_login')]"
        )
        assert btn.is_displayed()

    def test_access_portal_links_to_login(self, driver):
        """Access Portal button should link to user_login.php."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Access Portal')]"
        )
        assert "user_login.php" in btn.get_attribute("href")

    def test_access_portal_navigates_to_login(self, driver):
        """Clicking Access Portal should navigate to login page."""
        btn = driver.find_element(By.XPATH, "//a[contains(., 'Access Portal')]")
        btn.click()
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("user_login"))
        assert "user_login" in driver.current_url

    def test_security_terminal_button_exists(self, driver):
        """'Security Terminal' button should be visible."""
        btn = driver.find_element(
            By.XPATH, "//a[contains(., 'Security Terminal')]"
        )
        assert btn.is_displayed()

    def test_security_terminal_links_to_sub_admin_login(self, driver):
        """Security Terminal should link to sub_admin_login.php."""
        btn = driver.find_element(By.XPATH, "//a[contains(., 'Security Terminal')]")
        assert "sub_admin_login.php" in btn.get_attribute("href")

    def test_admin_console_button_exists(self, driver):
        """'Admin Console' button should be visible."""
        btn = driver.find_element(By.XPATH, "//a[contains(., 'Admin Console')]")
        assert btn.is_displayed()

    def test_admin_console_links_to_admin_login(self, driver):
        """Admin Console should link to admin_login.php."""
        btn = driver.find_element(By.XPATH, "//a[contains(., 'Admin Console')]")
        assert "admin_login.php" in btn.get_attribute("href")

    def test_admin_console_navigates_correctly(self, driver):
        """Clicking Admin Console should navigate to admin_login.php."""
        btn = driver.find_element(By.XPATH, "//a[contains(., 'Admin Console')]")
        btn.click()
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("admin_login"))
        assert "admin_login" in driver.current_url


class TestFeatureCards:
    def test_three_feature_cards_present(self, driver):
        """Three feature cards (Smart Verification, Live Intelligence, Instant Recognition) should exist."""
        cards = driver.find_elements(By.CSS_SELECTOR, ".glass-panel")
        assert len(cards) >= 3, f"Expected at least 3 feature cards, found {len(cards)}"

    def test_smart_verification_card_visible(self, driver):
        """'Smart Verification' feature card should be visible."""
        card = driver.find_element(By.XPATH, "//h3[contains(., 'Smart Verification')]")
        assert card.is_displayed()

    def test_live_intelligence_card_visible(self, driver):
        """'Live Intelligence' feature card should be visible."""
        card = driver.find_element(By.XPATH, "//h3[contains(., 'Live Intelligence')]")
        assert card.is_displayed()

    def test_instant_recognition_card_visible(self, driver):
        """'Instant Recognition' feature card should be visible."""
        card = driver.find_element(By.XPATH, "//h3[contains(., 'Instant Recognition')]")
        assert card.is_displayed()

    def test_qrcode_icon_present(self, driver):
        """QR code icon should be present in Smart Verification card."""
        icon = driver.find_element(By.CSS_SELECTOR, ".fa-qrcode")
        assert icon is not None

    def test_trophy_icon_present(self, driver):
        """Trophy icon should be present in Instant Recognition card."""
        icon = driver.find_element(By.CSS_SELECTOR, ".fa-trophy")
        assert icon is not None

    def test_chart_icon_present(self, driver):
        """Chart icon should be present in Live Intelligence card."""
        icon = driver.find_element(By.CSS_SELECTOR, ".fa-chart-pie")
        assert icon is not None


class TestExternalRegistrationButton:
    def test_register_button_shown_when_enabled(self, driver):
        """If external registration is enabled, a register button should appear."""
        btns = driver.find_elements(By.XPATH, "//a[contains(@href, 'register.php')]")
        if len(btns) == 0:
            pytest.skip("External registration is disabled — register button not shown")
        assert btns[0].is_displayed()

    def test_register_button_navigates_to_register(self, driver):
        """Register button should navigate to register.php."""
        btns = driver.find_elements(By.XPATH, "//a[contains(@href, 'register.php')]")
        if len(btns) == 0:
            pytest.skip("External registration is disabled")
        btns[0].click()
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("register.php"))
        assert "register.php" in driver.current_url


class TestMobileResponsiveness:
    def test_page_renders_on_mobile_viewport(self, driver):
        """No horizontal scroll at 393px width."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        scroll_width = driver.execute_script("return document.body.scrollWidth")
        window_width = driver.execute_script("return window.innerWidth")
        assert scroll_width <= window_width + 5, \
            f"Horizontal overflow on mobile: scrollWidth={scroll_width}, windowWidth={window_width}"
        driver.set_window_size(1280, 800)

    def test_hero_heading_visible_on_mobile(self, driver):
        """Hero heading should be visible on 393px viewport."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        heading = driver.find_element(By.TAG_NAME, "h1")
        assert heading.is_displayed()
        driver.set_window_size(1280, 800)

    def test_access_portal_button_visible_on_mobile(self, driver):
        """Access Portal button should be visible on mobile."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        btn = driver.find_element(By.XPATH, "//a[contains(., 'Access Portal')]")
        assert btn.is_displayed()
        driver.set_window_size(1280, 800)

    def test_nav_visible_on_mobile(self, driver):
        """Nav bar should be visible on mobile viewport."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        nav = driver.find_element(By.TAG_NAME, "nav")
        assert nav.is_displayed()
        driver.set_window_size(1280, 800)
