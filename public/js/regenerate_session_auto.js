let timeout;

function resetTimer() {
    clearTimeout(timeout);
    timeout = setTimeout(logout, 600000); // 10 minutes in milliseconds
}

function logout() {console.log('in session logout')
    localStorage.clear();
    sessionStorage.clear();
    window.location.href = '/logout';
}

// Events to reset the timer
window.onload = resetTimer;
window.onmousemove = resetTimer;
window.onkeypress = resetTimer;
window.onscroll = resetTimer;


