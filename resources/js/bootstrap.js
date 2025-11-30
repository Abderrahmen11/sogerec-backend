import axios from "axios";
window.axios = axios;

// Ensure axios sends cookies for cross-site requests
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL = "http://localhost:8000";
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
