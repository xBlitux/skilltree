import { expect, test } from '@playwright/test'

test('skill descriptions survive the API and appear only on actionable skill names', async ({ page, request }) => {
  const response = await request.get('api/analysis')
  expect(response.ok()).toBe(true)
  const data = await response.json()
  for (const skill of data.taxonomy.skills) expect(typeof skill.description).toBe('string')
  for (const skill of data.skills) {
    expect(skill.description).toBe(data.taxonomy.skills.find((entry: { id: number }) => entry.id === skill.id).description)
  }
  // Synthetic descriptions only in the browser response; no database writes.
  for (const skills of [data.skills, data.taxonomy.skills]) {
    for (const skill of skills) {
      if ([1, 14, 20].includes(skill.id)) skill.description = `  Beschreibung für ${skill.name}\nZweite Zeile <b>als Text</b>.  `
      else if (skill.id === 21) skill.description = ' \n\t '
      else if (skill.id === 22) skill.description = null
      else if (skill.id === 23) delete skill.description
      else skill.description = ''
    }
  }
  await page.route('**/api/analysis*', route => route.fulfill({ json: data }))
  await page.goto('./')
  const name = (text: string) => page.getByRole('button', { name: text, exact: true })
  const description = (text: string) => `Beschreibung für ${text}\nZweite Zeile <b>als Text</b>.`
  await name('Matrix').click()
  await name('Datenanalyse').hover()
  await expect(name('Datenanalyse')).toHaveAttribute('title', description('Datenanalyse'))
  for (const text of ['Datenverständnis', 'Statistische Grundlagen', 'Datenaufbereitung', 'SQL-Grundlagen']) {
    await expect(name(text)).not.toHaveAttribute('title')
  }
  await name('Datenanalyse').click()
  await expect(page.getByRole('combobox', { name: 'Person im Entwicklungspfad' })).toHaveValue('')
  await name('Start').click()
  for (const text of ['Testtaxonomie', 'Datenkompetenzen', 'Daten verarbeiten und analysieren', 'Analyse und Daten']) {
    await page.getByRole('button', { name: new RegExp(`^${text}:`) }).click()
  }
  await expect(name('Datenanalyse')).toHaveAttribute('title', description('Datenanalyse'))
  await expect(name('Datenverständnis')).not.toHaveAttribute('title')
  await name('Datenanalyse').click()
  await expect(page.getByRole('combobox', { name: 'Person im Entwicklungspfad' })).toHaveValue('')
  await page.getByRole('button', { name: 'Unkritisch: 16 Skills anzeigen' }).click()
  await expect(name('Datenanalyse')).toHaveAttribute('title', description('Datenanalyse'))
  await name('Datenanalyse').click()
  await expect(page.getByRole('combobox', { name: 'Person im Entwicklungspfad' })).toHaveValue('')
  for (const [category, text] of [['Handlungsbedarf: 7 Skills anzeigen', 'Budgetplanung'], ['Kritisch: 3 Skills anzeigen', 'Datenmigration']]) {
    await page.getByRole('button', { name: category, exact: true }).click()
    await expect(name(text!)).not.toHaveAttribute('title')
    await name(text!).click()
    await name(text!).hover()
    await expect(name(text!)).toHaveAttribute('title', description(text!))
    await name(`${text}: Details`).click()
    await expect(name(text!)).not.toHaveAttribute('title')
    await name(`${text}: Details`).click()
    await name(text!).click()
    await expect(page.getByRole('combobox', { name: 'Person im Entwicklungspfad' })).toHaveValue('')
  }
})
