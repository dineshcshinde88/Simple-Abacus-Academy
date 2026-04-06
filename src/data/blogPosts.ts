import { placeholderImages } from "@/data/placeholderImages";

export type BlogPost = {
  slug: string;
  title: string;
  date: string;
  excerpt: string;
  image: string;
  readTime: string;
  content: {
    heading: string;
    body: string[];
  }[];
};

export const blogPosts: BlogPost[] = [
  {
    slug: "benefits-of-teaching-kids-abacus",
    title: "10 Real Benefits of Teaching Kids How to Use an Abacus",
    date: "02 Mar 2026",
    excerpt: "How abacus training boosts your child's brain power and builds focus and memory.",
    image: placeholderImages.homeHero1,
    readTime: "6 min read",
    content: [
      {
        heading: "Why Abacus Still Matters",
        body: [
          "Abacus learning helps children visualize numbers and build strong number sense.",
          "It improves attention span, memory, and mental calculation speed over time.",
        ],
      },
      {
        heading: "Top Benefits Parents Notice",
        body: [
          "Better focus during homework and class activities.",
          "Faster recall and confidence in solving arithmetic problems.",
          "Improved hand eye coordination and mental agility.",
        ],
      },
    ],
  },
  {
    slug: "best-digital-abacus-for-kids",
    title: "Best Digital Abacus for Kids, Students and Teachers",
    date: "19 Feb 2026",
    excerpt: "Best digital abacus for kids, students and teachers for online classes and practice.",
    image: placeholderImages.homeHero2,
    readTime: "5 min read",
    content: [
      {
        heading: "Choosing the Right Digital Abacus",
        body: [
          "Look for a simple interface, clear bead visuals, and guided practice mode.",
          "Tools that combine worksheets, video lessons, and drills are most effective.",
        ],
      },
      {
        heading: "Who Benefits the Most",
        body: [
          "Students practicing at home need easy access and progress tracking.",
          "Teachers need downloadable worksheets and class-ready exercises.",
        ],
      },
    ],
  },
  {
    slug: "level-wise-worksheets-for-beginners",
    title: "Level-Wise Abacus Worksheets for Beginners",
    date: "06 Feb 2026",
    excerpt: "Abacus education is not just about learning mathematics or doing sums quickly.",
    image: placeholderImages.homeHero3,
    readTime: "4 min read",
    content: [
      {
        heading: "Start With The Basics",
        body: [
          "Beginner worksheets focus on number recognition and simple addition.",
          "Structured levels help children advance at a steady pace.",
        ],
      },
      {
        heading: "Build Consistency",
        body: [
          "Short daily practice sessions create lasting improvements.",
          "Printable worksheets keep practice focused and measurable.",
        ],
      },
    ],
  },
  {
    slug: "what-is-a-digital-abacus-frame",
    title: "What Is a Digital Abacus Frame? A Complete Guide",
    date: "27 Jan 2026",
    excerpt: "Learning maths is changing fast. Yes, the abacus has been part of it too.",
    image: placeholderImages.aboutHero,
    readTime: "5 min read",
    content: [
      {
        heading: "Digital Meets Traditional",
        body: [
          "A digital abacus frame simulates physical beads with guided practice.",
          "It enables remote learning, progress tracking, and instant feedback.",
        ],
      },
      {
        heading: "How It Helps Learners",
        body: [
          "Students stay engaged with interactive drills and step-by-step prompts.",
          "Parents can track practice time and accuracy trends easily.",
        ],
      },
    ],
  },
];
