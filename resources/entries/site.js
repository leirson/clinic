import '../css/site.css';
import { initSiteNavigation } from '../js/site-navigation.js';

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initSiteNavigation(), { once: true });
} else {
  initSiteNavigation();
}
