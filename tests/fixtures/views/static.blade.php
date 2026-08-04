<x-glider-img src="hero.jpg" glide-w="1200" glide-fit="crop" />
<x-glider-img-responsive src="gallery/photo.jpg" srcset-widths="400,800" glide-q="80" />
<x-glider-bg src="bg.jpg" glide-preset="hero" />
<x-glider-bg-responsive src="banner.jpg" preset="hero" />
<p>{{ Glider::url('inline.jpg', ['w' => 400, 'fm' => 'webp']) }}</p>
