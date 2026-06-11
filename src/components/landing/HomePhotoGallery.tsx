const homePhotos = [
  { src: "/assets/home-1.JPG", alt: "Students practicing abacus in class" },
  { src: "/assets/home-2.png", alt: "Simple Abacus classroom activity" },
  { src: "/assets/home-3.PNG", alt: "Children learning mental maths" },
];

const HomePhotoGallery = () => (
  <div className="relative">
    <div className="absolute inset-0 gradient-accent rounded-3xl -rotate-2 opacity-15" />
    <div className="relative grid grid-cols-2 gap-3">
      <img
        src={homePhotos[0].src}
        alt={homePhotos[0].alt}
        className="col-span-2 aspect-[3/2] w-full rounded-3xl bg-white object-contain shadow-2xl"
        loading="lazy"
      />
      {homePhotos.slice(1).map((photo) => (
        <img
          key={photo.src}
          src={photo.src}
          alt={photo.alt}
          className="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl"
          loading="lazy"
        />
      ))}
    </div>
  </div>
);

export default HomePhotoGallery;
