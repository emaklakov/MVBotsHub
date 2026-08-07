import { createApp } from 'vue'
import axios from 'axios'
import App from './App.vue'
import './styles/tokens.css'

// Настройка axios (до создания приложения)
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

async function init() {
    await axios.get('/sanctum/csrf-cookie')

    const el = document.getElementById('flow-editor')
    if (!el) throw new Error('Mount point #flow-editor not found')

    const botId = el.dataset.botId || ''
    const flowId = el.dataset.flowId || ''
    // Необязательный атрибут: пока платформа умеет только Telegram,
    // бэкенд может его вовсе не присылать — App.vue сам откатится на
    // канал по умолчанию (см. resolveChannelId в src/channels).
    const channelId = el.dataset.channel

    createApp(App, { botId, flowId, channelId }).mount(el)
}

init()
