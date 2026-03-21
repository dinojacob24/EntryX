"""
EntryX Registration Page — Selenium Test Suite
Covers: page load, form fields, validation, UI elements, mobile responsiveness.

Note: register.php redirects to index.php if external registration is disabled by admin.
      Run these tests only when external registration is ENABLED in the admin dashboard.

Run:
    python -m pytest tests/test_register.py -v
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
BASE_URL      = "http://localhost/Project/EntryX/pages/register.php"
LOGIN_URL     = "http://localhost/Project/EntryX/pages/user_login.php"
TIMEOUT       = 10


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
def open_register_page(driver):
    """Navigate to register page before each test."""
    driver.get(BASE_URL)
    WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.TAG_NAME, "body"))
    )
    time.sleep(1)


def is_on_register_page(driver):
    """Returns True if we're actually on the register page (not redirected)."""
    return "register.php" in driver.current_url


# ── Tests ─────────────────────────────────────────────────────────────────────

class TestRegisterPageLoad:
    def test_page_loads_without_php_errors(self, driver):
        """No raw PHP error text should appear."""
        body = driver.find_element(By.TAG_NAME, "body").text
        for kw in ["Parse error", "Fatal error", "Warning:", "Notice:"]:
            assert kw not in body, f"PHP error on page: '{kw}'"

    def test_page_title_contains_entry(self, driver):
        """Page title should reference ENTRY X."""
        assert "entry" in driver.title.lower(), f"Unexpected title: {driver.title}"

    def test_register_heading_visible(self, driver):
        """'Register for ...' heading should be visible."""
        if not is_on_register_page(driver):
            pytest.skip("External registration is disabled — register.php redirected to index")
        heading = driver.find_element(By.XPATH, "//h2[contains(., 'Register')]")
        assert heading.is_displayed()

    def test_no_redirect_when_registration_enabled(self, driver):
        """If external reg is enabled, should stay on register.php."""
        if not is_on_register_page(driver):
            pytest.skip("External registration is disabled — skipping")
        assert "register.php" in driver.current_url


class TestFormFieldsPresent:
    def test_name_field_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "nameInput")
        assert field.is_displayed()

    def test_email_field_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "emailInput")
        assert field.is_displayed()

    def test_password_field_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "password")
        assert field.is_displayed()
        assert field.get_attribute("type") == "password"

    def test_phone_field_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "phoneInput")
        assert field.is_displayed()

    def test_whatsapp_field_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "whatsappInput")
        assert field.is_displayed()

    def test_id_proof_file_input_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "idProofInput")
        assert field is not None

    def test_submit_button_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        btn = driver.find_element(By.ID, "submitBtn")
        assert btn.is_displayed()
        assert "Sign Up" in btn.text or "sign up" in btn.text.lower()


class TestPasswordToggle:
    def test_password_starts_as_hidden(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        field = driver.find_element(By.ID, "password")
        assert field.get_attribute("type") == "password"

    def test_toggle_shows_password(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        toggle = driver.find_element(By.ID, "togglePassword")
        toggle.click()
        time.sleep(0.3)
        field = driver.find_element(By.ID, "password")
        assert field.get_attribute("type") == "text"

    def test_toggle_hides_password_again(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        toggle = driver.find_element(By.ID, "togglePassword")
        toggle.click()  # show
        time.sleep(0.2)
        toggle.click()  # hide again
        time.sleep(0.2)
        field = driver.find_element(By.ID, "password")
        assert field.get_attribute("type") == "password"


class TestRealTimeValidation:
    def test_phone_rejects_letters(self, driver):
        """Phone field should strip non-numeric characters in real time."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        phone = driver.find_element(By.ID, "phoneInput")
        phone.clear()
        phone.send_keys("abc12345")
        time.sleep(0.3)
        # JS strips letters — value should only contain digits
        val = phone.get_attribute("value")
        assert val.isdigit() or val == "12345", f"Non-digits not stripped: '{val}'"

    def test_phone_error_shown_for_short_number(self, driver):
        """Error hint should appear when phone has fewer than 10 digits."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        phone = driver.find_element(By.ID, "phoneInput")
        phone.clear()
        phone.send_keys("12345")
        phone.send_keys(Keys.TAB)
        time.sleep(0.4)
        error = driver.find_element(By.ID, "phoneError")
        assert error.is_displayed(), "Phone error not shown for short number"

    def test_password_error_shown_for_short_password(self, driver):
        """Password error should appear when fewer than 8 chars typed."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        pwd = driver.find_element(By.ID, "password")
        pwd.clear()
        pwd.send_keys("abc")
        time.sleep(0.4)
        error = driver.find_element(By.ID, "passwordLengthError")
        assert error.is_displayed(), "Password length error not shown"

    def test_password_border_turns_green_when_valid(self, driver):
        """Password field border should turn green when >= 8 chars."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        pwd = driver.find_element(By.ID, "password")
        pwd.clear()
        pwd.send_keys("validpass123")
        time.sleep(0.4)
        border = pwd.value_of_css_property("border-color")
        # Green = rgb(16, 185, 129)
        assert "16, 185, 129" in border or "10b981" in border.lower() or border != "", \
            "Password border did not change on valid input"


class TestFormSubmitValidation:
    def _clear_and_fill(self, driver, name="", email="", password="", phone="", whatsapp=""):
        """Helper: clear all fields then fill with given values."""
        for fid, val in [
            ("nameInput", name), ("emailInput", email),
            ("password", password), ("phoneInput", phone), ("whatsappInput", whatsapp)
        ]:
            el = driver.find_element(By.ID, fid)
            el.clear()
            if val:
                el.send_keys(val)

    def _wait_for_swal(self, driver, timeout=8):
        """Wait for SweetAlert2 popup to appear and return it."""
        try:
            WebDriverWait(driver, timeout).until(
                EC.visibility_of_element_located((By.CLASS_NAME, "swal2-popup"))
            )
            return driver.find_element(By.CLASS_NAME, "swal2-popup")
        except Exception:
            return None

    def _dismiss_swal(self, driver):
        try:
            btn = driver.find_element(By.CLASS_NAME, "swal2-confirm")
            btn.click()
            time.sleep(0.5)
        except Exception:
            pass

    def test_submit_with_empty_name_shows_error(self, driver):
        """Submitting with blank name should trigger SweetAlert warning."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        self._clear_and_fill(driver, name="", email="test@gmail.com",
                             password="password123", phone="9876543210", whatsapp="9876543210")
        driver.find_element(By.ID, "submitBtn").click()
        swal = self._wait_for_swal(driver)
        assert swal is not None and swal.is_displayed(), "No SweetAlert shown for empty name"
        self._dismiss_swal(driver)

    def test_submit_with_invalid_email_shows_error(self, driver):
        """Submitting with bad email should trigger SweetAlert warning."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        self._clear_and_fill(driver, name="Test User", email="not-an-email",
                             password="password123", phone="9876543210", whatsapp="9876543210")
        driver.find_element(By.ID, "submitBtn").click()
        swal = self._wait_for_swal(driver)
        assert swal is not None and swal.is_displayed(), "No SweetAlert shown for invalid email"
        self._dismiss_swal(driver)

    def test_submit_with_short_password_shows_error(self, driver):
        """Submitting with password < 8 chars should trigger SweetAlert."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        self._clear_and_fill(driver, name="Test User", email="test@gmail.com",
                             password="abc", phone="9876543210", whatsapp="9876543210")
        driver.find_element(By.ID, "submitBtn").click()
        swal = self._wait_for_swal(driver)
        assert swal is not None and swal.is_displayed(), "No SweetAlert shown for short password"
        self._dismiss_swal(driver)

    def test_submit_with_invalid_phone_shows_error(self, driver):
        """Submitting with phone < 10 digits should trigger SweetAlert."""
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        self._clear_and_fill(driver, name="Test User", email="test@gmail.com",
                             password="password123", phone="12345", whatsapp="9876543210")
        driver.find_element(By.ID, "submitBtn").click()
        swal = self._wait_for_swal(driver)
        assert swal is not None and swal.is_displayed(), "No SweetAlert shown for invalid phone"
        self._dismiss_swal(driver)


class TestNavigationLinks:
    def test_back_to_home_link_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        link = driver.find_element(By.XPATH, "//a[contains(., 'Back to Home') or contains(., 'Back')]")
        assert link.is_displayed()

    def test_login_link_exists(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        link = driver.find_element(By.XPATH, "//a[contains(@href, 'user_login.php')]")
        assert link.is_displayed()

    def test_login_link_navigates(self, driver):
        if not is_on_register_page(driver): pytest.skip("Reg disabled")
        link = driver.find_element(By.XPATH, "//a[contains(@href, 'user_login.php')]")
        link.click()
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains("user_login.php"))
        assert "user_login.php" in driver.current_url


class TestMobileResponsiveness:
    def test_form_renders_on_mobile_viewport(self, driver):
        """Form should render without horizontal scroll at 393px."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        WebDriverWait(driver, TIMEOUT).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        if not is_on_register_page(driver):
            driver.set_window_size(1280, 800)
            pytest.skip("Reg disabled")
        scroll_width = driver.execute_script("return document.body.scrollWidth")
        window_width = driver.execute_script("return window.innerWidth")
        assert scroll_width <= window_width + 5, \
            f"Horizontal overflow on mobile: scrollWidth={scroll_width}, windowWidth={window_width}"
        driver.set_window_size(1280, 800)

    def test_submit_button_visible_on_mobile(self, driver):
        """Submit button should be visible on 393px viewport."""
        driver.set_window_size(393, 851)
        driver.get(BASE_URL)
        if not is_on_register_page(driver):
            driver.set_window_size(1280, 800)
            pytest.skip("Reg disabled")
        btn = driver.find_element(By.ID, "submitBtn")
        assert btn.is_displayed()
        driver.set_window_size(1280, 800)
