<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.CapacityUnit)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method string getRead()
 * @method void setRead(\string $value)
 * @method string getWrite()
 * @method void setWrite(\string $value)
 */
class CapacityUnit extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.CapacityUnit)
  
  /**
   * @var string $read
   * @tag 1
   * @label optional
   * @type \ProtocolBuffers::TYPE_INT32
   **/
  protected $read;
  
  /**
   * @var string $write
   * @tag 2
   * @label optional
   * @type \ProtocolBuffers::TYPE_INT32
   **/
  protected $write;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.CapacityUnit)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.CapacityUnit)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_INT32,
        "name"     => "read",
        "required" => false,
        "optional" => true,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_INT32,
        "name"     => "write",
        "required" => false,
        "optional" => true,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.CapacityUnit)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}