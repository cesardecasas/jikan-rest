<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Jikan\Helper\Media;
use Jikan\Helper\Parser;
use Jikan\Jikan;
use Jikan\Model\Common\YoutubeMeta;

class Anime extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mal_id','url','title','title_english','title_japanese','title_synonyms','type','source','episodes','status','airing','aired','duration','rating','score','scored_by','rank','popularity','members','favorites','synopsis','background','premiered','broadcast','related','producers','licensors','studios','genres','opening_themes','ending_themes'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['trailer', 'season', 'year', 'themes', 'images'];

    protected $mainDataRequest = true;
    protected $databaseStoreAvailability = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anime';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
//    protected $primaryKey = 'mal_id';
//
//    const CREATED_AT = 'creation_date';
//    const UPDATED_AT = 'last_update';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        '_id', 'expiresAt', 'request_hash', 'trailer_url', 'premiered', 'opening_themes', 'ending_themes', 'images'
    ];

    public function setRelatedAttribute($value)
    {
        $this->attributes['related'] = $this->getRelatedAttribute();
    }

    public function getRelatedAttribute()
    {
        // Fix JSON response for empty related object
        if (\count($this->attributes['related']) === 0) {
            $this->attributes['related'] = new \stdClass();
        }

        if (!is_object($this->attributes['related']) && !empty($this->attributes['related'])) {
            $relation = [];
            foreach ($this->attributes['related'] as $relationType => $related) {
                $relation[] = [
                    'relation' => $relationType,
                    'items' => $related
                ];
            }
            $this->attributes['related'] = $relation;
        }

        return $this->attributes['related'];
    }

    public function setTrailerAttribute($value)
    {
        $this->attributes['trailer'] = $this->getTrailerAttribute();
    }

    public function getTrailerAttribute()
    {
        try {
            $youtubeId = Media::youtubeIdFromUrl($this->attributes['trailer_url']);
            $youtubeUrl = Media::generateYoutubeUrlFromId($youtubeId);
        } catch (\Exception $e) {
            return [
                'youtube_id' => null,
                'url' => null,
                'embed_url' => null
            ];
        }

        return [
            'youtube_id' => $youtubeId,
            'url' => $youtubeUrl,
            'embed_url' => $this->attributes['trailer_url']
        ];
    }

    public function setSeasonAttribute($value)
    {
        $this->attributes['season'] = $this->getSeasonAttribute();
    }

    public function getSeasonAttribute()
    {
        $premiered = $this->attributes['premiered'];

        if (empty($premiered)
            || is_null($premiered)
            || !preg_match('~(Winter|Spring|Summer|Fall|)\s([\d+]{4})~', $premiered)
        ) {
            return null;
        }

        return explode(' ', $premiered)[0];
    }

    public function setYearAttribute($value)
    {
        $this->attributes['year'] = $this->getYearAttribute();
    }

    public function getYearAttribute()
    {
        $premiered = $this->attributes['premiered'];

        if (empty($premiered)
            || is_null($premiered)
            || !preg_match('~(Winter|Spring|Summer|Fall|)\s([\d+]{4})~', $premiered)
        ) {
            return null;
        }

        return (int) explode(' ', $premiered)[1];
    }

    public function setBroadcastAttribute($value)
    {
        $this->attributes['year'] = $this->getBroadcastAttribute();
    }

    public function getBroadcastAttribute()
    {
        $broadcastStr = $this->attributes['broadcast'];

        if (is_null($broadcastStr)) {
            return null;
        }

        if (preg_match('~(.*) at (.*) \(~', $broadcastStr, $matches)) {
            return [
                'day' => $matches[1],
                'time' => $matches[2],
                'timezone' => 'Asia/Tokyo',
                'string' => $broadcastStr
            ];
        }

        return null;
    }

    public function setThemesAttribute($value)
    {
        $this->attributes['themes'] = $this->getThemesAttribute();
    }

    public function getThemesAttribute()
    {
        return [
            'opening' => $this->attributes['opening_themes'],
            'ending' => $this->attributes['ending_themes'],
        ];
    }

    public function setImageAttribute($value)
    {
        $this->attributes['image'] = $this->getImageAttribute();
    }

    public function getImageAttribute()
    {
        $imageUrl = $this->attributes['image_url'];

        return [
            'jpg' => [
                'image_url' => $imageUrl,
                'small_image_url' => str_replace('.jpg', 't.jpg', $imageUrl),
                'large_image_url' => str_replace('.jpg', 'l.jpg', $imageUrl),
            ],
            'webp' => [
                'image_url' => str_replace('.jpg', '.webp', $imageUrl),
                'small_image_url' => str_replace('.jpg', 't.webp', $imageUrl),
                'large_image_url' => str_replace('.jpg', 'l.webp', $imageUrl),
            ]
        ];
    }
}