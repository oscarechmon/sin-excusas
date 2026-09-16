import { api as client } from './client'

/** Fotos y textos editables de la web pública. */
export const siteContentApi = {
  async list() {
    const { data } = await client.get('/site-contents')
    return data
  },

  async update(key: string, payload: { title?: string | null; text?: string | null }) {
    const { data } = await client.patch(`/site-contents/${key}`, payload)
    return data
  },

  /** El header explícito evita que axios convierta el FormData a JSON. */
  async uploadImage(key: string, file: File) {
    const body = new FormData()
    body.append('image', file)
    const { data } = await client.post(`/site-contents/${key}/image`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  async removeImage(key: string) {
    const { data } = await client.delete(`/site-contents/${key}/image`)
    return data
  },
}
