import { createI18n } from 'vue-i18n'

export default (messages) => {
  return createI18n({
    locale: 'uk',
    fallbackLocale: 'en',
    messages
  })
}
