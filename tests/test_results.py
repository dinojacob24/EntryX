"""
EntryX Hall of Fame / Results Page — Selenium Test Suite
Covers: page load, heading, search form, search functionality,
        result cards, empty state, navigation, mobile responsiveness.

Run:
    python -m pytest tests/test_results.py -v
"""

import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager

# ── Config ────────────────────────────────────────────────────────────────────
BASE_URL = "http://localhost/Project/EntryX/pages/results.php"
TIMEOUT  = 10


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
def open_results_page(driver):
    """Navigate to results page before each test."""
    driver.get(BASE_URL)
    WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.TAG_NAME, "body"))
    )
    time.sleep(0.8)


# ── Tests ─────────────────────────────────────────────────────────────────────

class TestResultsPageLoad:
    def test_page_loads_without_php_errors(self, driver):
        """No raw PHP error text should appear on the page."""
        body = driver.find_element(By.TAG_NAME, "body").text
        for kw in ["Parse error", "Fatal error", "Warning:", "Notice:"]:
            assert kw not in body, f"PHP error on page: '{kw}'"

    def test_page_title_contains_entry(self, driver):
        """Page title should reference ENTRY X."""
        assert "entry" in driver.title.lower(), f"Unexpected title: {driver.title}"

    def test_hall_of_fame_heading_visible(self, driver):
        """'Hall of Fame' heading should be visible."""
        heading = driver.find_element(By.XPATH, "//h1[contains(., 'Hall')]")
        assert heading.is_displayed()

    def test_subtitle_visible(self, driver):
        """Subtitle about achievements should be visible."""
        body = driver.find_element(By.TAG_NAME, "body").text
        assert "Achievements" in body or "Legends" in body or "Celebrating" in body


class TestSearchForm:
    def test_search_input_exists(self, driver):
        """Search input field should be present."""
        field = driver.find_element(By.CSS_SELECTOR, "input[name='search']")
        assert field.is_displayed()

    def test_search_button_exists(self, driver):
        """Search button should be present."""
        btn = driver.find_element(By.XPATH, "//button[contains(., 'Search')]")
        assert btn.is_displayed()

    def test_search_input_accepts_text(self, driver):
        """Search field should accept typed text."""
        field = driver.find_element(By.CSS_SELECTOR, "input[name='search']")
        field.clear()
        field.send_keys("dance")
        assert field.get_attribute("value") == "dance"

    def test_search_submits_via_enter(self, driver):
        """Pressing Enter in search field should submit and update URL."""
        field = driver.find_element(By.CSS_SELECTOR, "input[name='search']")
        field.clear()
        field.send_keys("test_query_xyz")
        field.send_keys(Keys.RETURN)
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("search="))
        assert "search=test_query_xyz" in driver.current_url

    def test_search_submits_via_button(self, driver):
        """Clicking Search button should submit and update URL."""
        field = driver.find_element(By.CSS_SELECTOR, "input[name='search']")
        field.clear()
        field.send_keys("event_btn_test")
        btn = driver.find_element(By.XPATH, "//button[contains(., 'Search')]")
        btn.click()
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("search="))
        assert "search=event_btn_test" in driver.current_url

    def test_search_value_persists_in_field(self, driver):
        """After search, the query should remain in the input field."""
        driver.get(BASE_URL + "?search=persistence_test")
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "input[name='search']"))
        )
        field = driver.find_element(By.CSS_SELECTOR, "input[name='search']")
        assert field.get_attribute("value") == "persistence_test"

    def test_empty_search_returns_all_results(self, driver):
        """Submitting empty search should show all results (no filter)."""
        driver.get(BASE_URL + "?search=")
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        # Should not show an error — either results or empty state panel
        body = driver.find_element(By.TAG_NAME, "body").text
        assert "Parse error" not in body and "Fatal error" not in body


class TestResultCards:
    def _has_results(self, driver):
        """Check if any result cards are present on the page."""
        cards = driver.find_elements(By.XPATH, "//div[contains(., 'GOLD RECORD')]")
        return len(cards) > 0

    def test_result_cards_or_empty_state_shown(self, driver):
        """Page should show either result cards or the empty state panel."""
        cards = driver.find_elements(By.XPATH, "//div[contains(., 'GOLD RECORD')]")
        empty = driver.find_elements(By.XPATH, "//p[contains(., 'No historical records')]")
        assert len(cards) > 0 or len(empty) > 0, \
            "Neither result cards nor empty state found"

    def test_gold_record_badge_visible(self, driver):
        """If results exist, GOLD RECORD badge should be visible on cards."""
        if not self._has_results(driver):
            pytest.skip("No results in DB — skipping card content tests")
        badge = driver.find_element(By.XPATH, "//div[text()='GOLD RECORD']")
        assert badge.is_displayed()

    def test_winner_name_visible_on_card(self, driver):
        """If results exist, winner name heading should be visible."""
        if not self._has_results(driver):
            pytest.skip("No results in DB")
        # Winner name is in an h2 inside the card
        winner = driver.find_element(By.XPATH, "//h2[ancestor::div[contains(@class,'glass-panel')]]")
        assert winner.is_displayed()
        assert len(winner.text.strip()) > 0, "Winner name is empty"

    def test_grand_champion_label_visible(self, driver):
        """'Grand Champion' label should appear on result cards."""
        if not self._has_results(driver):
            pytest.skip("No results in DB")
        label = driver.find_element(By.XPATH, "//p[contains(text(), 'Grand Champion')]")
        assert label.is_displayed()

    def test_event_name_shown_on_card(self, driver):
        """Event name row should be visible on result cards."""
        if not self._has_results(driver):
            pytest.skip("No results in DB")
        event_label = driver.find_element(By.XPATH, "//span[contains(text(), 'Event')]")
        assert event_label.is_displayed()

    def test_trophy_icon_present(self, driver):
        """Trophy icon should be present on result cards."""
        if not self._has_results(driver):
            pytest.skip("No results in DB")
        icon = driver.find_element(By.CSS_SELECTOR, ".fa-trophy")
        assert icon is not None


class TestEmptyState:
    def test_empty_state_shown_for_no_match_search(self, driver):
        """Searching for a nonsense query should show the empty state message."""
        driver.get(BASE_URL + "?search=zzz_no_match_xyz_123")
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        body = driver.find_element(By.TAG_NAME, "body").text
        assert "No historical records" in body, \
            "Empty state message not shown for no-match search"

    def test_empty_state_has_icon(self, driver):
        """Empty state should show the hourglass icon."""
        driver.get(BASE_URL + "?search=zzz_no_match_xyz_123")
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        icon = driver.find_element(By.CSS_SELECTOR, ".fa-hourglass-empty")
        assert icon is not None


class TestNavigation:
    def test_back_to_home_link_exists(self, driver):
        """'Back to Home' link should be present."""
        link = driver.find_element(By.XPATH, "//a[contains(., 'Back to Home') or contains(., 'Back')]")
        assert link.is_displayed()

    def test_back_link_navigates_away(self, driver):
        """Clicking Back should navigate away from results page."""
        link = driver.find_element(By.XPATH, "//a[contains(., 'Back to Home') or contains(., 'Back')]")
        link.click()
        time.sleep(1.5)
        assert "results.php" not in driver.current_url, \
            "Back link did not navigate away from results page"

    def test_nav_bar_present(self, driver):
        """Global nav bar should be present on the page."""
        nav = driver.find_element(By.TAG_NAME, "nav")
        assert nav.is_displayed()


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

    def test_search_form_visible_on_mobile(self, driver):
        """Search input should be visible on 393px viewport."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "input[name='search']"))
        )
        field = driver.find_element(By.CSS_SELECTOR, "input[name='search']")
        assert field.is_displayed()
        driver.set_window_size(1280, 800)

    def test_heading_visible_on_mobile(self, driver):
        """Hall of Fame heading should be visible on mobile."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        heading = driver.find_element(By.XPATH, "//h1[contains(., 'Hall')]")
        assert heading.is_displayed()
        driver.set_window_size(1280, 800)
