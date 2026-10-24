import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Notes from '../views/Notes.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guestOnly: true },
  },
  {
    path: '/',
    name: 'Notes',
    component: Notes,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');

  if (to.meta.requiresAuth && !token) {
    next({ name: 'Login' });
  } else if (to.meta.guestOnly && token) {
    next({ name: 'Notes' });
  } else {
    next();
  }
});

export default router;
