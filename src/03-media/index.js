const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { MediaUpload, RichText } = wp.editor;
const { Button, IconButton } = wp.components;

console.info(wp.components);

import { ReactComponent as Logo } from "../bv-logo.svg";
import logoWhiteURL from "../bv-logo-white.svg";

registerBlockType("ryankit/media", {
  title: __("Custom title and image", "ryankit"),
  icon: { src: Logo },
  category: "ryankit",
  attributes: {
    epsiodeTitle: {
      type: "string",
      source: "html",
      selector: ".ryankit-title"
    },
    episodeImage: {
      type: "string",
      source: "attribute",
      selector: ".ryankit-logo img",
      attribute: "src",
      default: logoWhiteURL
    }
  },

  edit: props => {

    // Lift info from props and populate various constants.
    const {
      attributes: { epsiodeTitle, episodeImage },
      className,
      setAttributes
    } = props;

    // Grab newEpisodeTitle, set the value of episodeTitle to newEpisodeTitle.
    const onChangeEpisodeTitle = newEpisodeTitle => {
      setAttributes({ epsiodeTitle: newEpisodeTitle});
    };

    // Grab imageObject, set the value of episodeImage to imageObject.sizes.ryankitFeatImg.url.
    const onImageSelect = imageObject => {
      setAttributes({ episodeImage: imageObject.sizes.ryankitFeatImg.url });
    };

    return (
      <div className={`${className} ryankit-block ryankit-editable`}>
        <figure className="ryankit-logo">
          <img src={episodeImage} alt="logo" />
          <MediaUpload
            onSelect={onImageSelect}
            type="image"
            value={episodeImage}
            render={({ open }) => (
              <IconButton
                className="ryankit-logo__button"
                onClick={open}
                icon="format-image"
                showTooltip="true"
                label={__("Change image.", "ryankit")}
              /> 
            )}
          />
        </figure>
        <div className="ryankit-info">
          <div className="ryankit-nameplate">
            {__("Custom Block image", "ryankit")}
          </div>
          <h3 className="ryankit-title">
            <RichText 
              placeholder={__("Add your title", "ryankit")}
              value={epsiodeTitle}
              onChange={onChangeEpisodeTitle}
            />
          </h3>
        </div>
      </div>
    );
  },
  save: props => {
    const {
      attributes: { epsiodeTitle, episodeImage }
    } = props;

    return (
      <div className="ryankit-block ryankit-static">
        <figure className="ryankit-logo">
          <img src={episodeImage} alt="logo" />
        </figure>
        <div className="ryankit-info">
          <div className="ryankit-nameplate">
            {__("Custom Block image", "ryankit")}
          </div>
          <h3 className="ryankit-title">
            <RichText.Content value={epsiodeTitle} />
          </h3>
          <div className="ryankit-cta">
            <a href="/subscribe">{__("Like & Subscribe!", "ryankit")}</a>
          </div>
        </div>
      </div>
    );
  }
});
