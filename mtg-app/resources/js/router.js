import Vue from 'vue'
import VueRouter from 'vue-router'
import Deck from './components/Deck.vue'


Vue.use(VueRouter)

const routes = [
    {
        path: '/deck/:id',
        name: 'deck',
        component: Deck,
        props: true
    }
    // Add other routes as needed
]

const router = new VueRouter({
    mode: 'history',
    routes
})

export default router