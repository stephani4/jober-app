/**
 * Личное имя из ФИО: второе слово («Губин Степан Денисович» → «Степан»).
 * Если слова одно — оно и есть имя.
 */
export function givenNameFromFio(fullName: string | null | undefined): string {
  const words = (fullName ?? '').trim().split(/\s+/).filter(Boolean)
  if (words.length >= 2) {
    return words[1]
  }
  return words[0] ?? ''
}
