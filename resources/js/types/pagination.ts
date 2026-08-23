export interface SimplePaginated<T> {
  data: T[]
  current_page: number
  current_page_url: string
  first_page_url: string
  from: number
  next_page_url: string
  path: string
  per_page: number
  prev_page_url: string
  to: number
}
