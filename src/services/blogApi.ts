import { getApiBase } from "@/lib/apiBase";

export type BlogCategory = {
  id: number;
  category_name: string;
  slug: string;
};

export type BlogPost = {
  id: number;
  category_id: number | null;
  title: string;
  slug: string;
  author: string;
  publish_date: string | null;
  excerpt: string | null;
  description?: string | null;
  featured_image?: string | null;
  featured_image_url: string;
  category_name: string | null;
  category_slug: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
  meta_keywords?: string | null;
};

type BlogListResponse = {
  blogs: BlogPost[];
  categories: BlogCategory[];
  total: number;
  page: number;
  totalPages: number;
};

function blogApiUrl(params: Record<string, string | number | undefined>) {
  const apiBase = getApiBase();
  const origin = new URL(apiBase, window.location.origin).origin;
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      search.set(key, String(value));
    }
  });
  return `${origin}/abacus-spark-learn-main/admin/blog_api.php?${search.toString()}`;
}

export async function fetchBlogs(params: { search?: string; category?: string; page?: number; limit?: number } = {}) {
  const response = await fetch(blogApiUrl({ action: "list", ...params }));
  if (!response.ok) {
    throw new Error("Unable to load blogs");
  }
  return response.json() as Promise<BlogListResponse>;
}

export async function fetchBlogDetail(slug: string) {
  const response = await fetch(blogApiUrl({ action: "detail", slug }));
  if (!response.ok) {
    throw new Error("Blog not found");
  }
  return response.json() as Promise<{ blog: BlogPost }>;
}
