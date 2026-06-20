import { createApp } from 'vue';
import CustomerGroups from './components/CustomerGroups.vue';

const app = createApp({});
app.component('customer-groups', CustomerGroups);
app.mount('#app');
