import axios from 'axios'
import type { FlowSchema, FlowVersion } from '@/types/flow'

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

// CSRF токен для Sanctum (если нужен)
axios.defaults.headers.common['X-XSRF-TOKEN'] = decodeURIComponent(
    document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''
)

export function useFlowApi(botId: string, flowId: string) {
    const base = `/flow-editor/bots/${botId}/flows/${flowId}`

    const getDraft = async (): Promise<FlowVersion> => {
        const { data } = await axios.get(`${base}/draft`)
        return data.draft
    }

    const saveDraft = async (schema: FlowSchema) => {
        const { data } = await axios.post(`${base}/save-draft`, { schema })
        return data
    }

    const publish = async () => {
        const { data } = await axios.post(`${base}/publish`)
        return data
    }

    return { getDraft, saveDraft, publish }
}
