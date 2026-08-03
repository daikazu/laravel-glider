<x-glider-img :src="$post->image" glide-w="400" />
<x-glider-img src="{{ $hero }}" />
<p>{{ Glider::url($variable, ['w' => 100]) }}</p>
