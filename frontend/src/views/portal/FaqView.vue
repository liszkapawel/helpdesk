<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import axios from 'axios'
import { getOrgSlug } from '@/utils/subdomain'

const articles = ref<any[]>([])
const loading = ref(true)

async function loadFaq() {
  loading.value = true
  try {
    const slug = getOrgSlug()
    const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8080'
    const { data } = await axios.get(`${baseUrl}/api/public/org/${slug}/faq`)
    articles.value = data
  } finally {
    loading.value = false
  }
}

onMounted(loadFaq)
</script>

<template>
  <div class="max-w-3xl mx-auto py-8">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold tracking-tight mb-2">Baza wiedzy</h1>
      <p class="text-surface-500">Odpowiedzi na najczęściej zadawane pytania</p>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <i class="pi pi-spin pi-spinner text-4xl text-surface-400"></i>
    </div>

    <div v-else-if="!articles.length" class="text-center py-16 text-surface-400">
      <i class="pi pi-book text-4xl mb-3"></i>
      <p>Brak artykułów FAQ</p>
    </div>

    <Accordion v-else>
      <AccordionPanel v-for="article in articles" :key="article.id" :value="String(article.id)">
        <AccordionHeader>{{ article.title }}</AccordionHeader>
        <AccordionContent>
          <p class="whitespace-pre-wrap text-surface-700 leading-relaxed">{{ article.content }}</p>
        </AccordionContent>
      </AccordionPanel>
    </Accordion>
  </div>
</template>
