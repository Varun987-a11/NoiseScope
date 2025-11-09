// js/login_handler.js

async function handleLogin(e, role) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const statusMessage = document.getElementById('loginMessage');
    const loginButton = document.getElementById('loginButton');

    let targetScript;
    let redirectPage;

    if (role === 'admin') {
        targetScript = 'backend/login_admin.php';
        redirectPage = 'admin_dashboard.php';
    } else if (role === 'user') {
        targetScript = 'backend/login_user.php'; 
        redirectPage = 'user_submit.php'; 
    } else {
        statusMessage.textContent = 'Error: Invalid login role specified.';
        return;
    }

    loginButton.disabled = true;
    loginButton.textContent = 'Authenticating...';
    statusMessage.textContent = '';
    
    try {
        const response = await fetch(targetScript, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, password: password })
        });

        // CRITICAL: Check for non-200 status codes (like 404 or 500)
        if (!response.ok) {
            throw new Error(`Server returned status: ${response.status}`);
        }

        const result = await response.json();

        if (result.status === 'success') {
            statusMessage.style.color = 'green';
            statusMessage.textContent = 'Login successful! Redirecting...';
            
            setTimeout(() => {
                window.location.href = redirectPage;
            }, 1000);

        } else {
            statusMessage.style.color = 'red';
            statusMessage.textContent = result.message || 'Login failed. Check credentials.';
        }
    } catch (error) {
        statusMessage.style.color = 'red';
        // This is the source of your error message:
        statusMessage.textContent = 'Network error. Could not reach the server or server returned an error.'; 
        console.error('Fetch error:', error);
    } finally {
        loginButton.disabled = false;
        loginButton.textContent = 'Log In';
    }
}