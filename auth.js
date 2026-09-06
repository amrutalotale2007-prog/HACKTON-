// SkillBridge Client-side Authentication Store (Standalone HTML mode)

const DEFAULT_USERS = [
    {
        id: 1,
        full_name: "Demo Student",
        email: "student@skillbridge.com",
        password: "password123",
        role: "student"
    },
    {
        id: 2,
        full_name: "Prof. Demo Academic",
        email: "academic@skillbridge.com",
        password: "password123",
        role: "academician"
    },
    {
        id: 3,
        full_name: "Demo Industry Partner",
        email: "industry@skillbridge.com",
        password: "password123",
        role: "industry"
    }
];

function getUsers() {
    const raw = localStorage.getItem("skillbridge_users");
    if (!raw) {
        localStorage.setItem("skillbridge_users", JSON.stringify(DEFAULT_USERS));
        return DEFAULT_USERS;
    }
    try {
        return JSON.parse(raw) || DEFAULT_USERS;
    } catch (e) {
        return DEFAULT_USERS;
    }
}

function saveUser(newUser) {
    const users = getUsers();
    users.push(newUser);
    localStorage.setItem("skillbridge_users", JSON.stringify(users));
}

function getCurrentUser() {
    const raw = sessionStorage.getItem("skillbridge_session") || localStorage.getItem("skillbridge_session");
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

function setCurrentUser(user, remember = true) {
    const data = JSON.stringify({
        user_id: user.id,
        full_name: user.full_name || user.name || "User",
        email: user.email,
        role: (user.role || "student").toLowerCase()
    });
    sessionStorage.setItem("skillbridge_session", data);
    if (remember) {
        localStorage.setItem("skillbridge_session", data);
    }
}

function logout() {
    sessionStorage.removeItem("skillbridge_session");
    localStorage.removeItem("skillbridge_session");
    window.location.href = "login.html";
}

function requireAuth(allowedRoles = null) {
    const user = getCurrentUser();
    if (!user) {
        window.location.href = "login.html";
        return null;
    }
    if (allowedRoles && Array.isArray(allowedRoles) && !allowedRoles.includes(user.role)) {
        if (user.role === "academician") window.location.href = "academician-dashboard.html";
        else if (user.role === "industry") window.location.href = "industry-dashboard.html";
        else window.location.href = "dashboard.html";
        return null;
    }
    return user;
}
