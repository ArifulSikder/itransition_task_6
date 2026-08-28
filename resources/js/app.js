import { createApp } from 'vue';
import DashboardPage from './pages/DashboardPage.vue';
import EditorPage from './pages/EditorPage.vue';
import HomePage from './pages/HomePage.vue';

const pages = {
    home: HomePage,
    dashboard: DashboardPage,
    editor: EditorPage,
};

const el = document.getElementById('app');

if (el) {
    const page = pages[el.dataset.page];
    let props = {};

    try {
        props = el.dataset.props ? JSON.parse(el.dataset.props) : {};
    } catch {
        props = {};
    }

    if (page) {
        createApp(page, props).mount(el);
    }
}
